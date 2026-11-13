<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(private CheckoutService $checkout, private InventoryService $inventory) {}

    public function show(Request $request, CartController $cart)
    {
        $props = $cart->cartProps($request);
        if (empty($props['lines'])) {
            return redirect()->route('cart');
        }

        $customer = Auth::guard('customer')->user();

        return Inertia::render('Storefront/Checkout', $props + [
            'customer' => $customer?->only(['name', 'email', 'phone']),
            'addresses' => $customer?->addresses()->get() ?? [],
        ]);
    }

    public function place(Request $request, CartController $cart)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:120',
            'phone' => 'required|string|max:20',
            'delivery_method' => 'required|in:delivery,pickup',
            'address_line1' => 'required_if:delivery_method,delivery|nullable|string|max:200',
            'city' => 'required_if:delivery_method,delivery|nullable|string|max:100',
            'payment_method' => 'required|in:mpesa,card,bank_transfer',
        ]);

        $props = $cart->cartProps($request);
        if (empty($props['lines'])) {
            return redirect()->route('cart');
        }

        $lines = array_map(fn ($l) => ['variant_id' => $l['variant_id'], 'qty' => $l['qty']], $props['lines']);
        $warehouseId = \App\Models\Warehouse::where('code', 'MAIN')->value('id');
        $token = CartController::token($request);

        try {
            $order = $this->checkout->placeOrder(
                $lines,
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'delivery_method' => $data['delivery_method'],
                    'address' => $data['delivery_method'] === 'delivery'
                        ? ['line1' => $data['address_line1'], 'city' => $data['city']]
                        : null,
                ],
                $token,
                $warehouseId,
                Auth::guard('customer')->id(),
                $request->session()->get('coupon'),
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        // UAT: simulate instant provider verification for the demo flow.
        // Production: Daraja STK push + webhook callback marks the payment verified.
        $this->checkout->confirmPayment($order, $data['payment_method'], 'SIM-'.strtoupper(uniqid()));

        $request->session()->forget(['cart', 'coupon', 'cart_token']);

        return redirect()->route('checkout.confirmation', $order->reference);
    }

    public function confirmation(string $reference)
    {
        $order = Order::where('reference', $reference)->with('items')->firstOrFail();

        return Inertia::render('Storefront/Confirmation', [
            'order' => [
                'reference' => $order->reference,
                'status' => $order->status,
                'total_cents' => $order->total_cents,
                'items' => $order->items->map->only(['name_snapshot', 'sku_snapshot', 'qty', 'unit_price_cents', 'line_total_cents']),
                'email' => $order->customerEmail(),
            ],
        ]);
    }
}
