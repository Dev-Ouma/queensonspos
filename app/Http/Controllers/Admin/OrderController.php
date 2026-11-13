<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(private CheckoutService $checkout) {}

    public function index(Request $request)
    {
        $query = Order::with('customer')->latest('placed_at');
        if ($request->filled('q')) {
            $query->where('reference', 'ilike', "%{$request->q}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('from')) {
            $query->where('placed_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('placed_at', '<=', $request->to.' 23:59:59');
        }

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $query->paginate(15)->withQueryString()->through(fn ($o) => [
                'id' => $o->id,
                'reference' => $o->reference,
                'customer' => $o->customerName(),
                'status' => $o->status,
                'payment_status' => $o->payment_status,
                'total_cents' => $o->total_cents,
                'placed_at' => $o->placed_at?->format('d M Y H:i'),
            ]),
            'statuses' => array_keys(CheckoutService::TRANSITIONS),
            'filters' => $request->only(['q', 'status', 'payment_status', 'from', 'to']),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'statusHistory', 'payments', 'customer', 'delivery']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'reference' => $order->reference,
                'customer' => $order->customerName(),
                'email' => $order->customerEmail(),
                'phone' => $order->customer?->phone ?? $order->guest_phone,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'subtotal_cents' => $order->subtotal_cents,
                'discount_cents' => $order->discount_cents,
                'delivery_cents' => $order->delivery_cents,
                'total_cents' => $order->total_cents,
                'delivery_method' => $order->delivery_method,
                'delivery_address' => $order->delivery_address,
                'placed_at' => $order->placed_at?->format('d M Y H:i'),
                'items' => $order->items->map->only(['name_snapshot', 'sku_snapshot', 'qty', 'unit_price_cents', 'line_total_cents']),
                'payments' => $order->payments->map(fn ($p) => [
                    'reference' => $p->reference, 'method' => $p->method, 'amount_cents' => $p->amount_cents,
                    'status' => $p->status, 'provider_txn_id' => $p->provider_txn_id, 'at' => $p->created_at->format('d M Y H:i'),
                ]),
                'history' => $order->statusHistory->map(fn ($h) => [
                    'from' => $h->from_status, 'to' => $h->to_status, 'note' => $h->note, 'at' => $h->created_at->format('d M Y H:i'),
                ]),
                'allowed_transitions' => CheckoutService::TRANSITIONS[$order->status] ?? [],
            ],
        ]);
    }

    public function transition(Request $request, Order $order)
    {
        $data = $request->validate(['to' => 'required|string', 'note' => 'nullable|string|max:300']);
        try {
            $this->checkout->transition($order, $data['to'], $request->user()->id, $data['note'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        }

        return back()->with('success', "Order moved to {$data['to']}.");
    }
}
