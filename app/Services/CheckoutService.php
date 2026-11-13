<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CheckoutService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Place an order from cart lines: [['variant_id' => int, 'qty' => int], ...].
     * Reserves stock, creates the order awaiting payment.
     */
    public function placeOrder(array $lines, array $customerData, string $cartToken, int $warehouseId, ?int $customerId = null, ?string $couponCode = null): Order
    {
        return DB::transaction(function () use ($lines, $customerData, $cartToken, $warehouseId, $customerId, $couponCode) {
            $subtotal = 0;
            $items = [];
            foreach ($lines as $line) {
                $variant = ProductVariant::with('product')->findOrFail($line['variant_id']);
                $qty = max(1, (int) $line['qty']);
                if ($this->inventory->available($variant->id, $warehouseId) < $qty) {
                    throw new RuntimeException("{$variant->product->name} ({$variant->sku}) is out of stock");
                }
                $lineTotal = $variant->price_cents * $qty;
                $subtotal += $lineTotal;
                $items[] = [
                    'variant' => $variant,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            $discount = 0;
            $coupon = null;
            if ($couponCode) {
                $coupon = Coupon::where('code', strtoupper($couponCode))->first();
                if ($coupon && $coupon->isValid($subtotal)) {
                    $discount = $coupon->discountFor($subtotal);
                } else {
                    $coupon = null;
                }
            }

            $deliveryFee = ($customerData['delivery_method'] ?? 'delivery') === 'delivery'
                ? (int) (\App\Models\Setting::where('key', 'delivery_fee_cents')->value('value') ?? 30000)
                : 0;
            if ($coupon?->type === 'free_delivery') {
                $deliveryFee = 0;
            }

            $order = Order::create([
                'reference' => 'QNS-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                'customer_id' => $customerId,
                'guest_email' => $customerId ? null : ($customerData['email'] ?? null),
                'guest_name' => $customerId ? null : ($customerData['name'] ?? null),
                'guest_phone' => $customerId ? null : ($customerData['phone'] ?? null),
                'status' => 'awaiting_payment',
                'payment_status' => 'unpaid',
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'delivery_cents' => $deliveryFee,
                'total_cents' => $subtotal - $discount + $deliveryFee,
                'coupon_id' => $coupon?->id,
                'delivery_method' => $customerData['delivery_method'] ?? 'delivery',
                'delivery_address' => $customerData['address'] ?? null,
                'warehouse_id' => $warehouseId,
                'placed_at' => now(),
            ]);

            foreach ($items as $i) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'variant_id' => $i['variant']->id,
                    'name_snapshot' => $i['variant']->product->name,
                    'sku_snapshot' => $i['variant']->sku,
                    'qty' => $i['qty'],
                    'unit_price_cents' => $i['variant']->price_cents,
                    'cost_cents_snapshot' => $i['variant']->cost_cents,
                    'line_total_cents' => $i['line_total'],
                ]);
                $this->inventory->reserve($i['variant']->id, $warehouseId, $i['qty'], $cartToken);
            }

            OrderStatusHistory::create(['order_id' => $order->id, 'to_status' => 'awaiting_payment']);
            $coupon?->increment('used_count');

            return $order;
        });
    }

    /** Confirm payment (verified provider txn or manual verification) and deduct stock. */
    public function confirmPayment(Order $order, string $method, ?string $providerTxnId = null, ?int $userId = null): Payment
    {
        return DB::transaction(function () use ($order, $method, $providerTxnId, $userId) {
            $payment = Payment::create([
                'reference' => 'PAY-'.strtoupper(Str::ulid()),
                'subject_type' => Order::class,
                'subject_id' => $order->id,
                'amount_cents' => $order->total_cents,
                'method' => $method,
                'provider' => $method === 'mpesa' ? 'daraja' : null,
                'provider_txn_id' => $providerTxnId,
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            $cartToken = \App\Models\StockReservation::where('order_id', $order->id)->value('cart_token')
                ?? \App\Models\StockReservation::where('status', 'held')
                    ->whereIn('variant_id', $order->items->pluck('variant_id'))
                    ->value('cart_token');

            if ($cartToken) {
                app(InventoryService::class)->convertReservations($cartToken, $order->id, $userId);
            } else {
                foreach ($order->items as $item) {
                    app(InventoryService::class)->record(
                        $item->variant_id, $order->warehouse_id, -$item->qty, 'online_sale',
                        Order::class, $order->id, $userId,
                    );
                }
            }

            $this->transition($order, 'paid', $userId, 'Payment verified');
            $order->update(['payment_status' => 'paid']);

            if ($order->customer_id) {
                $points = intdiv($order->total_cents, 10000); // 1 point per KES 100
                if ($points > 0) {
                    $order->customer->increment('loyalty_points', $points);
                    \App\Models\LoyaltyTransaction::create([
                        'customer_id' => $order->customer_id,
                        'points' => $points,
                        'reason' => 'order_paid',
                        'subject_type' => Order::class,
                        'subject_id' => $order->id,
                    ]);
                }
            }

            return $payment;
        });
    }

    public const TRANSITIONS = [
        'pending' => ['awaiting_payment', 'cancelled'],
        'awaiting_payment' => ['paid', 'cancelled'],
        'paid' => ['confirmed', 'refunded'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['packed'],
        'packed' => ['ready_for_dispatch'],
        'ready_for_dispatch' => ['dispatched'],
        'dispatched' => ['out_for_delivery'],
        'out_for_delivery' => ['delivered'],
        'delivered' => ['completed', 'return_requested'],
        'completed' => ['return_requested'],
        'return_requested' => ['returned', 'completed'],
        'returned' => ['refunded'],
    ];

    public function transition(Order $order, string $to, ?int $userId = null, ?string $note = null): void
    {
        $allowed = self::TRANSITIONS[$order->status] ?? [];
        if (! in_array($to, $allowed, true)) {
            throw new RuntimeException("Cannot transition order from {$order->status} to {$to}");
        }
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $order->status,
            'to_status' => $to,
            'user_id' => $userId,
            'note' => $note,
        ]);
        $order->update(['status' => $to]);
    }
}
