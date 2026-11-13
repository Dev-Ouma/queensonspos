<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OperationsController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function customers(Request $request)
    {
        $query = Customer::withCount('orders')->withSum('orders as total_spent', 'total_cents');
        if ($request->filled('q')) {
            $query->where(fn ($w) => $w->where('name', 'ilike', "%{$request->q}%")->orWhere('email', 'ilike', "%{$request->q}%"));
        }

        return Inertia::render('Admin/Customers', [
            'customers' => $query->latest()->paginate(15)->withQueryString()->through(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'email' => $c->email, 'phone' => $c->phone,
                'orders_count' => $c->orders_count, 'total_spent_cents' => (int) $c->total_spent,
                'loyalty_points' => $c->loyalty_points, 'status' => $c->status,
                'joined' => $c->created_at->format('d M Y'),
            ]),
            'filters' => $request->only(['q']),
        ]);
    }

    public function suppliers()
    {
        return Inertia::render('Admin/Suppliers', [
            'suppliers' => Supplier::withCount('purchaseOrders')->orderBy('name')->get(),
        ]);
    }

    public function supplierSave(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|exists:suppliers,id',
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'address' => 'nullable|string|max:250',
            'payment_terms' => 'nullable|string|max:100',
            'active' => 'boolean',
        ]);
        ! empty($data['id']) ? Supplier::find($data['id'])->update($data) : Supplier::create($data);

        return back()->with('success', 'Supplier saved.');
    }

    public function purchaseOrders()
    {
        return Inertia::render('Admin/Purchases/Index', [
            'orders' => PurchaseOrder::with(['supplier:id,name', 'warehouse:id,name'])->latest()->paginate(15)
                ->through(fn ($po) => [
                    'id' => $po->id, 'reference' => $po->reference, 'supplier' => $po->supplier->name,
                    'warehouse' => $po->warehouse->name, 'status' => $po->status, 'total_cents' => $po->total_cents,
                    'created' => $po->created_at->format('d M Y'),
                ]),
            'suppliers' => Supplier::where('active', true)->get(['id', 'name']),
            'warehouses' => Warehouse::get(['id', 'name']),
        ]);
    }

    public function purchaseOrderStore(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $request) {
            $po = PurchaseOrder::create([
                'reference' => 'PO-'.now()->format('ymd').'-'.strtoupper(Str::random(4)),
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'approved',
                'created_by' => $request->user()->id,
                'approved_by' => $request->user()->id,
            ]);
            $total = 0;
            foreach ($data['items'] as $i) {
                $cost = (int) round($i['unit_cost'] * 100);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'variant_id' => $i['variant_id'],
                    'qty_ordered' => $i['qty'],
                    'unit_cost_cents' => $cost,
                ]);
                $total += $cost * $i['qty'];
            }
            $po->update(['total_cents' => $total]);
        });

        return back()->with('success', 'Purchase order created.');
    }

    public function purchaseOrderShow(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'warehouse', 'items.variant.product', 'grns.items']);

        return Inertia::render('Admin/Purchases/Show', [
            'po' => [
                'id' => $purchaseOrder->id,
                'reference' => $purchaseOrder->reference,
                'supplier' => $purchaseOrder->supplier->name,
                'warehouse' => $purchaseOrder->warehouse->name,
                'status' => $purchaseOrder->status,
                'total_cents' => $purchaseOrder->total_cents,
                'items' => $purchaseOrder->items->map(fn ($i) => [
                    'id' => $i->id,
                    'product' => $i->variant->product->name,
                    'sku' => $i->variant->sku,
                    'qty_ordered' => $i->qty_ordered,
                    'qty_received' => $i->qty_received,
                    'unit_cost_cents' => $i->unit_cost_cents,
                ]),
            ],
        ]);
    }

    public function receiveGoods(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'supplier_delivery_ref' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.qty_received' => 'required|integer|min:0',
            'items.*.qty_damaged' => 'integer|min:0',
        ]);

        DB::transaction(function () use ($data, $request, $purchaseOrder) {
            $grn = GoodsReceivedNote::create([
                'reference' => 'GRN-'.now()->format('ymd').'-'.strtoupper(Str::random(4)),
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_delivery_ref' => $data['supplier_delivery_ref'] ?? null,
                'received_by' => $request->user()->id,
            ]);

            foreach ($data['items'] as $i) {
                $poItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->findOrFail($i['purchase_order_item_id']);
                $accepted = (int) $i['qty_received'];
                $damaged = (int) ($i['qty_damaged'] ?? 0);
                if ($accepted + $damaged === 0) {
                    continue;
                }
                if ($poItem->qty_received + $accepted + $damaged > $poItem->qty_ordered) {
                    throw new \RuntimeException("Receiving more than ordered for {$poItem->variant->sku}");
                }
                GrnItem::create([
                    'goods_received_note_id' => $grn->id,
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => $accepted,
                    'qty_damaged' => $damaged,
                ]);
                if ($accepted > 0) {
                    $this->inventory->record(
                        $poItem->variant_id, $purchaseOrder->warehouse_id, $accepted,
                        'purchase_receipt', GoodsReceivedNote::class, $grn->id, $request->user()->id,
                    );
                    // Latest cost becomes the variant cost.
                    ProductVariant::whereKey($poItem->variant_id)->update(['cost_cents' => $poItem->unit_cost_cents]);
                }
                if ($damaged > 0) {
                    $this->inventory->record(
                        $poItem->variant_id, $purchaseOrder->warehouse_id, $damaged,
                        'damaged', GoodsReceivedNote::class, $grn->id, $request->user()->id, 'Damaged on receipt', 'damaged',
                    );
                }
                $poItem->increment('qty_received', $accepted + $damaged);
            }

            $fullyReceived = $purchaseOrder->items()->whereColumn('qty_received', '<', 'qty_ordered')->doesntExist();
            $purchaseOrder->update(['status' => $fullyReceived ? 'received' : 'partially_received']);
        });

        return back()->with('success', 'Goods received and stock updated.');
    }

    public function expenses(Request $request)
    {
        return Inertia::render('Admin/Expenses', [
            'expenses' => Expense::with(['category', 'branch'])->latest('incurred_on')->paginate(15)
                ->through(fn ($e) => [
                    'id' => $e->id, 'description' => $e->description, 'category' => $e->category->name,
                    'branch' => $e->branch?->name, 'amount_cents' => $e->amount_cents,
                    'incurred_on' => $e->incurred_on->format('d M Y'), 'status' => $e->status,
                ]),
            'categories' => ExpenseCategory::get(['id', 'name']),
            'branches' => \App\Models\Branch::get(['id', 'name']),
        ]);
    }

    public function expenseStore(Request $request)
    {
        $data = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'required|string|max:250',
            'amount' => 'required|numeric|min:0.01',
            'incurred_on' => 'required|date',
        ]);
        Expense::create([
            'expense_category_id' => $data['expense_category_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'description' => $data['description'],
            'amount_cents' => (int) round($data['amount'] * 100),
            'incurred_on' => $data['incurred_on'],
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Expense recorded.');
    }

    public function expenseApprove(Request $request, Expense $expense)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);
        $expense->update(['status' => $request->status, 'approved_by' => $request->user()->id]);

        return back();
    }
}
