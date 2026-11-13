<?php

namespace App\Services;

use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class InventoryService
{
    /**
     * Record a stock movement inside a transaction with a row lock.
     * Positive delta adds stock, negative removes. Throws if result would go negative.
     */
    public function record(
        int $variantId,
        int $warehouseId,
        int $qtyDelta,
        string $movementType,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?int $userId = null,
        ?string $reason = null,
        string $bucket = 'on_hand',
    ): InventoryTransaction {
        if ($qtyDelta === 0) {
            throw new RuntimeException('qty_delta must not be zero');
        }

        return DB::transaction(function () use ($variantId, $warehouseId, $qtyDelta, $movementType, $subjectType, $subjectId, $userId, $reason, $bucket) {
            $balance = InventoryBalance::query()
                ->where('variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::create([
                    'variant_id' => $variantId,
                    'warehouse_id' => $warehouseId,
                ]);
                $balance = InventoryBalance::whereKey($balance->id)->lockForUpdate()->first();
            }

            $new = $balance->{$bucket} + $qtyDelta;
            if ($new < 0) {
                throw new RuntimeException("Insufficient stock for variant {$variantId} in warehouse {$warehouseId}");
            }
            $balance->{$bucket} = $new;
            $balance->save();

            return InventoryTransaction::create([
                'reference' => 'ITX-'.strtoupper(Str::ulid()),
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'qty_delta' => $qtyDelta,
                'movement_type' => $movementType,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'user_id' => $userId,
                'reason' => $reason,
            ]);
        });
    }

    public function available(int $variantId, int $warehouseId): int
    {
        $b = InventoryBalance::where('variant_id', $variantId)->where('warehouse_id', $warehouseId)->first();

        return $b ? $b->on_hand - $b->reserved - $b->damaged : 0;
    }

    /** Reserve stock for a cart during checkout. */
    public function reserve(int $variantId, int $warehouseId, int $qty, string $cartToken, int $ttlMinutes = 15): StockReservation
    {
        return DB::transaction(function () use ($variantId, $warehouseId, $qty, $cartToken, $ttlMinutes) {
            $balance = InventoryBalance::query()
                ->where('variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $balance || ($balance->on_hand - $balance->reserved - $balance->damaged) < $qty) {
                throw new RuntimeException('Insufficient stock to reserve');
            }

            $balance->increment('reserved', $qty);

            return StockReservation::create([
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'cart_token' => $cartToken,
                'qty' => $qty,
                'status' => 'held',
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]);
        });
    }

    /** Convert held reservations into a confirmed sale deduction. */
    public function convertReservations(string $cartToken, int $orderId, ?int $userId = null): void
    {
        DB::transaction(function () use ($cartToken, $orderId, $userId) {
            $reservations = StockReservation::where('cart_token', $cartToken)->where('status', 'held')->lockForUpdate()->get();
            foreach ($reservations as $r) {
                InventoryBalance::where('variant_id', $r->variant_id)
                    ->where('warehouse_id', $r->warehouse_id)
                    ->lockForUpdate()
                    ->decrement('reserved', $r->qty);

                $this->record(
                    $r->variant_id, $r->warehouse_id, -$r->qty, 'online_sale',
                    \App\Models\Order::class, $orderId, $userId,
                );

                $r->update(['status' => 'converted', 'order_id' => $orderId]);
            }
        });
    }

    public function releaseReservations(string $cartToken): void
    {
        DB::transaction(function () use ($cartToken) {
            $reservations = StockReservation::where('cart_token', $cartToken)->where('status', 'held')->lockForUpdate()->get();
            foreach ($reservations as $r) {
                InventoryBalance::where('variant_id', $r->variant_id)
                    ->where('warehouse_id', $r->warehouse_id)
                    ->lockForUpdate()
                    ->decrement('reserved', $r->qty);
                $r->update(['status' => 'released']);
            }
        });
    }

    /** Release every reservation past its expiry. Run from scheduler. */
    public function releaseExpired(): int
    {
        $count = 0;
        StockReservation::where('status', 'held')->where('expires_at', '<', now())
            ->pluck('cart_token')->unique()
            ->each(function ($token) use (&$count) {
                $this->releaseReservations($token);
                $count++;
            });

        return $count;
    }
}
