<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosCashMovement;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\ProductVariant;
use App\Models\Register;
use App\Services\PosService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PosController extends Controller
{
    public function __construct(private PosService $pos) {}

    public function terminal(Request $request)
    {
        $session = $this->openSessionFor($request->user()->id);

        return Inertia::render('Pos/Terminal', [
            'session' => $session ? [
                'id' => $session->id,
                'register' => $session->register->name,
                'branch' => $session->register->branch->name,
                'opened_at' => $session->opened_at->format('d M Y H:i'),
                'opening_cash_cents' => $session->opening_cash_cents,
                'sales_count' => $session->sales()->count(),
                'sales_total_cents' => (int) $session->sales()->where('status', '!=', 'voided')->sum('total_cents'),
            ] : null,
            'registers' => Register::with('branch:id,name')->where('active', true)->get()
                ->map(fn ($r) => ['id' => $r->id, 'label' => "{$r->branch->name} — {$r->name}"]),
            'canDiscount' => $request->user()->can('pos.discount'),
        ]);
    }

    public function openSession(Request $request)
    {
        $data = $request->validate([
            'register_id' => 'required|exists:registers,id',
            'opening_cash' => 'required|numeric|min:0',
        ]);
        try {
            $this->pos->openSession($data['register_id'], $request->user()->id, (int) round($data['opening_cash'] * 100));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['session' => $e->getMessage()]);
        }

        return back()->with('success', 'Register opened.');
    }

    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        return ProductVariant::with('product')
            ->where('active', true)
            ->where(fn ($w) => $w->where('sku', 'ilike', "%$q%")
                ->orWhere('barcode', $q)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'ilike', "%$q%")))
            ->limit(10)->get()
            ->map(fn ($v) => [
                'variant_id' => $v->id,
                'name' => $v->product->name,
                'sku' => $v->sku,
                'size' => $v->size,
                'color' => $v->color,
                'price_cents' => $v->price_cents,
                'available' => $v->availableQty(),
            ]);
    }

    public function sale(Request $request)
    {
        $data = $request->validate([
            'lines' => 'required|array|min:1',
            'lines.*.variant_id' => 'required|exists:product_variants,id',
            'lines.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,mpesa,card,bank_transfer',
            'discount' => 'numeric|min:0',
            'provider_txn_id' => 'nullable|string|max:60',
        ]);

        $session = $this->openSessionFor($request->user()->id);
        if (! $session) {
            return back()->withErrors(['session' => 'No open register session.']);
        }

        $discountCents = (int) round(($data['discount'] ?? 0) * 100);
        if ($discountCents > 0 && ! $request->user()->can('pos.discount')) {
            return back()->withErrors(['discount' => 'You are not authorized to apply discounts.']);
        }

        try {
            $sale = $this->pos->completeSale(
                $session,
                $data['lines'],
                $data['payment_method'],
                $discountCents,
                providerTxnId: $data['provider_txn_id'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['sale' => $e->getMessage()]);
        }

        return back()->with('success', "Sale {$sale->reference} completed.")->with('lastSale', [
            'reference' => $sale->reference,
            'total_cents' => $sale->total_cents,
        ]);
    }

    public function receipt(string $reference)
    {
        $sale = PosSale::where('reference', $reference)->with(['items', 'session.register.branch', 'payments'])->firstOrFail();

        return Inertia::render('Pos/Receipt', [
            'sale' => [
                'reference' => $sale->reference,
                'branch' => $sale->session->register->branch->name,
                'cashier' => $sale->session->user->name,
                'at' => $sale->created_at->format('d M Y H:i'),
                'items' => $sale->items->map->only(['name_snapshot', 'sku_snapshot', 'qty', 'unit_price_cents', 'line_total_cents']),
                'subtotal_cents' => $sale->subtotal_cents,
                'discount_cents' => $sale->discount_cents,
                'total_cents' => $sale->total_cents,
                'method' => $sale->payments->first()?->method,
            ],
        ]);
    }

    public function saleLookup(Request $request)
    {
        $request->validate(['reference' => 'required|string']);
        $sale = PosSale::where('reference', $request->reference)->with('items')->first();
        if (! $sale) {
            return response()->json(['error' => 'Sale not found'], 404);
        }

        return response()->json([
            'id' => $sale->id,
            'reference' => $sale->reference,
            'status' => $sale->status,
            'total_cents' => $sale->total_cents,
            'items' => $sale->items->map(fn ($i) => [
                'pos_sale_item_id' => $i->id,
                'name' => $i->name_snapshot,
                'sku' => $i->sku_snapshot,
                'qty' => $i->qty,
                'unit_price_cents' => $i->unit_price_cents,
            ]),
        ]);
    }

    public function processReturn(Request $request)
    {
        $data = $request->validate([
            'sale_id' => 'required|exists:pos_sales,id',
            'items' => 'required|array|min:1',
            'items.*.pos_sale_item_id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:1',
            'condition' => 'required|in:resellable,damaged',
            'reason' => 'nullable|string|max:200',
        ]);

        $session = $this->openSessionFor($request->user()->id);
        if (! $session) {
            return back()->withErrors(['session' => 'No open register session.']);
        }

        try {
            $return = $this->pos->processReturn(
                PosSale::findOrFail($data['sale_id']), $session, $data['items'],
                $data['condition'], $data['reason'] ?? null, $request->user()->id,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }

        return back()->with('success', "Return {$return->reference} processed. Refund KES ".number_format($return->refund_cents / 100, 2));
    }

    public function cashMovement(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:cash_in,cash_out',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:200',
        ]);
        $session = $this->openSessionFor($request->user()->id);
        if (! $session) {
            return back()->withErrors(['session' => 'No open register session.']);
        }
        PosCashMovement::create([
            'pos_session_id' => $session->id,
            'type' => $data['type'],
            'amount_cents' => (int) round($data['amount'] * 100),
            'reason' => $data['reason'],
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Cash movement recorded.');
    }

    public function closeSession(Request $request)
    {
        $data = $request->validate(['actual_cash' => 'required|numeric|min:0']);
        $session = $this->openSessionFor($request->user()->id);
        if (! $session) {
            return back()->withErrors(['session' => 'No open register session.']);
        }
        $session = $this->pos->closeSession($session, (int) round($data['actual_cash'] * 100));

        return back()->with('success', 'Register closed. Variance: KES '.number_format($session->variance_cents / 100, 2));
    }

    private function openSessionFor(int $userId): ?PosSession
    {
        return PosSession::with('register.branch')->where('user_id', $userId)->where('status', 'open')->first();
    }
}
