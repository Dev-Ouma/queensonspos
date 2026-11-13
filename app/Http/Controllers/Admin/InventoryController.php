<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request)
    {
        $query = InventoryBalance::with(['variant.product', 'warehouse']);
        if ($request->filled('q')) {
            $query->whereHas('variant', fn ($q) => $q->where('sku', 'ilike', "%{$request->q}%")
                ->orWhereHas('product', fn ($p) => $p->where('name', 'ilike', "%{$request->q}%")));
        }
        if ($request->filled('warehouse')) {
            $query->where('warehouse_id', $request->warehouse);
        }
        if ($request->boolean('low_only')) {
            $query->whereRaw('(on_hand - reserved - damaged) <= 5');
        }

        return Inertia::render('Admin/Inventory/Index', [
            'balances' => $query->orderBy('id')->paginate(20)->withQueryString()->through(fn ($b) => [
                'id' => $b->id,
                'product' => $b->variant->product->name,
                'sku' => $b->variant->sku,
                'size' => $b->variant->size,
                'color' => $b->variant->color,
                'warehouse' => $b->warehouse->name,
                'on_hand' => $b->on_hand,
                'reserved' => $b->reserved,
                'damaged' => $b->damaged,
                'available' => $b->available,
                'variant_id' => $b->variant_id,
                'warehouse_id' => $b->warehouse_id,
            ]),
            'warehouses' => Warehouse::get(['id', 'name']),
            'filters' => $request->only(['q', 'warehouse', 'low_only']),
        ]);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'qty_delta' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:200',
        ]);

        try {
            $this->inventory->record(
                $data['variant_id'], $data['warehouse_id'], $data['qty_delta'],
                'adjustment', null, null, $request->user()->id, $data['reason'],
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['adjust' => $e->getMessage()]);
        }

        return back()->with('success', 'Stock adjusted.');
    }

    public function ledger(Request $request)
    {
        $query = InventoryTransaction::with(['variant.product', 'warehouse', 'user'])->latest('created_at');
        if ($request->filled('variant')) {
            $query->where('variant_id', $request->variant);
        }
        if ($request->filled('type')) {
            $query->where('movement_type', $request->type);
        }

        return Inertia::render('Admin/Inventory/Ledger', [
            'transactions' => $query->paginate(25)->withQueryString()->through(fn ($t) => [
                'reference' => $t->reference,
                'product' => $t->variant->product->name,
                'sku' => $t->variant->sku,
                'warehouse' => $t->warehouse->name,
                'qty_delta' => $t->qty_delta,
                'type' => $t->movement_type,
                'user' => $t->user?->name ?? 'System',
                'reason' => $t->reason,
                'at' => $t->created_at->format('d M Y H:i'),
            ]),
            'types' => InventoryTransaction::distinct()->pluck('movement_type'),
            'filters' => $request->only(['variant', 'type']),
        ]);
    }

    public function variantSearch(Request $request)
    {
        $q = $request->get('q', '');

        return ProductVariant::with('product')
            ->where(fn ($w) => $w->where('sku', 'ilike', "%$q%")->orWhere('barcode', $q)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'ilike', "%$q%")))
            ->where('active', true)
            ->limit(12)->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'label' => "{$v->product->name} — {$v->color}/{$v->size} ({$v->sku})",
                'price_cents' => $v->price_cents,
                'available' => $v->availableQty(),
            ]);
    }
}
