<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSession;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PosService
{
    public function __construct(private InventoryService $inventory) {}

    public function openSession(int $registerId, int $userId, int $openingCashCents): PosSession
    {
        if (PosSession::where('register_id', $registerId)->where('status', 'open')->exists()) {
            throw new RuntimeException('Register already has an open session');
        }

        return PosSession::create([
            'register_id' => $registerId,
            'user_id' => $userId,
            'opening_cash_cents' => $openingCashCents,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    /**
     * Complete a POS sale. Lines: [['variant_id', 'qty'], ...].
     * Deducts stock from the branch warehouse immediately.
     */
    public function completeSale(PosSession $session, array $lines, string $paymentMethod, int $discountCents = 0, ?int $customerId = null, ?string $providerTxnId = null): PosSale
    {
        $warehouseId = $session->register->branch->warehouses()->value('id');
        if (! $warehouseId) {
            throw new RuntimeException('Branch has no warehouse configured');
        }

        return DB::transaction(function () use ($session, $lines, $paymentMethod, $discountCents, $customerId, $providerTxnId, $warehouseId) {
            $subtotal = 0;
            $prepared = [];
            foreach ($lines as $line) {
                $variant = ProductVariant::with('product')->findOrFail($line['variant_id']);
                $qty = max(1, (int) $line['qty']);
                $lineTotal = $variant->price_cents * $qty;
                $subtotal += $lineTotal;
                $prepared[] = compact('variant', 'qty', 'lineTotal');
            }

            $discountCents = min($discountCents, $subtotal);
            $sale = PosSale::create([
                'reference' => 'POS-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                'pos_session_id' => $session->id,
                'customer_id' => $customerId,
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discountCents,
                'total_cents' => $subtotal - $discountCents,
                'status' => 'completed',
            ]);

            foreach ($prepared as $p) {
                PosSaleItem::create([
                    'pos_sale_id' => $sale->id,
                    'variant_id' => $p['variant']->id,
                    'name_snapshot' => $p['variant']->product->name,
                    'sku_snapshot' => $p['variant']->sku,
                    'qty' => $p['qty'],
                    'unit_price_cents' => $p['variant']->price_cents,
                    'cost_cents_snapshot' => $p['variant']->cost_cents,
                    'line_total_cents' => $p['lineTotal'],
                ]);
                $this->inventory->record(
                    $p['variant']->id, $warehouseId, -$p['qty'], 'pos_sale',
                    PosSale::class, $sale->id, $session->user_id,
                );
            }

            Payment::create([
                'reference' => 'PAY-'.strtoupper(Str::ulid()),
                'subject_type' => PosSale::class,
                'subject_id' => $sale->id,
                'amount_cents' => $sale->total_cents,
                'method' => $paymentMethod,
                'provider' => $paymentMethod === 'mpesa' ? 'daraja' : null,
                'provider_txn_id' => $providerTxnId,
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            return $sale;
        });
    }

    public function processReturn(PosSale $sale, PosSession $session, array $items, string $condition, ?string $reason, int $userId): PosReturn
    {
        $warehouseId = $session->register->branch->warehouses()->value('id');

        return DB::transaction(function () use ($sale, $session, $items, $condition, $reason, $userId, $warehouseId) {
            $refund = 0;
            $return = PosReturn::create([
                'reference' => 'RET-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                'pos_sale_id' => $sale->id,
                'pos_session_id' => $session->id,
                'refund_cents' => 0,
                'reason' => $reason,
                'condition' => $condition,
                'user_id' => $userId,
            ]);

            foreach ($items as $i) {
                $saleItem = PosSaleItem::where('pos_sale_id', $sale->id)->findOrFail($i['pos_sale_item_id']);
                $qty = min((int) $i['qty'], $saleItem->qty);
                if ($qty < 1) {
                    continue;
                }
                $alreadyReturned = PosReturnItem::whereHas('saleItem', fn ($q) => $q->where('id', $saleItem->id))
                    ->whereHas('saleItem.sale.items')
                    ->where('pos_sale_item_id', $saleItem->id)->sum('qty');
                if ($alreadyReturned + $qty > $saleItem->qty) {
                    throw new RuntimeException("Return quantity exceeds sold quantity for {$saleItem->sku_snapshot}");
                }
                PosReturnItem::create([
                    'pos_return_id' => $return->id,
                    'pos_sale_item_id' => $saleItem->id,
                    'qty' => $qty,
                ]);
                $refund += $saleItem->unit_price_cents * $qty;

                $this->inventory->record(
                    $saleItem->variant_id, $warehouseId, $qty, 'customer_return',
                    PosReturn::class, $return->id, $userId, $reason,
                    $condition === 'damaged' ? 'damaged' : 'on_hand',
                );
            }

            $return->update(['refund_cents' => $refund]);
            $sale->update(['status' => $refund >= $sale->total_cents ? 'refunded' : 'partially_refunded']);

            return $return;
        });
    }

    public function closeSession(PosSession $session, int $actualCashCents): PosSession
    {
        $cashSales = Payment::where('subject_type', PosSale::class)
            ->whereIn('subject_id', $session->sales()->pluck('id'))
            ->where('method', 'cash')->sum('amount_cents');
        $cashIn = $session->cashMovements()->where('type', 'cash_in')->sum('amount_cents');
        $cashOut = $session->cashMovements()->where('type', 'cash_out')->sum('amount_cents');
        $cashRefunds = $session->hasMany(PosReturn::class)->sum('refund_cents');

        $expected = $session->opening_cash_cents + $cashSales + $cashIn - $cashOut - $cashRefunds;

        $session->update([
            'closing_cash_cents' => $actualCashCents,
            'expected_cash_cents' => $expected,
            'variance_cents' => $actualCashCents - $expected,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return $session;
    }
}
