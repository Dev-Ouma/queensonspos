<?php

use App\Models\Branch;
use App\Models\Order;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Register;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\PosService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->warehouse = Warehouse::where('code', 'MAIN')->first();
    $this->variant = ProductVariant::first();
});

it('tracks inventory through the ledger with correct balances', function () {
    $inv = app(InventoryService::class);
    $start = $inv->available($this->variant->id, $this->warehouse->id);

    $inv->record($this->variant->id, $this->warehouse->id, 10, 'purchase_receipt');
    expect($inv->available($this->variant->id, $this->warehouse->id))->toBe($start + 10);

    $inv->record($this->variant->id, $this->warehouse->id, -4, 'pos_sale');
    expect($inv->available($this->variant->id, $this->warehouse->id))->toBe($start + 6);
});

it('refuses stock movements that would go negative', function () {
    $inv = app(InventoryService::class);
    $available = $inv->available($this->variant->id, $this->warehouse->id);

    $inv->record($this->variant->id, $this->warehouse->id, -($available + 1), 'adjustment', reason: 'test');
})->throws(RuntimeException::class);

it('places an online order, reserves stock, and deducts on payment', function () {
    $inv = app(InventoryService::class);
    $checkout = app(CheckoutService::class);
    $before = $inv->available($this->variant->id, $this->warehouse->id);

    $order = $checkout->placeOrder(
        [['variant_id' => $this->variant->id, 'qty' => 2]],
        ['name' => 'Test Buyer', 'email' => 'buyer@test.com', 'phone' => '0700000000', 'delivery_method' => 'pickup'],
        'cart-token-1',
        $this->warehouse->id,
    );

    expect($order->status)->toBe('awaiting_payment');
    // Reserved, so available drops immediately.
    expect($inv->available($this->variant->id, $this->warehouse->id))->toBe($before - 2);

    $checkout->confirmPayment($order, 'mpesa', 'TESTTXN123');
    $order->refresh();

    expect($order->status)->toBe('paid');
    expect($order->payment_status)->toBe('paid');
    expect($inv->available($this->variant->id, $this->warehouse->id))->toBe($before - 2);
    // Reservation converted: on_hand reduced, reserved back to 0.
    $balance = $this->variant->balances()->where('warehouse_id', $this->warehouse->id)->first();
    expect($balance->reserved)->toBe(0);
});

it('releases expired reservations', function () {
    $inv = app(InventoryService::class);
    $before = $inv->available($this->variant->id, $this->warehouse->id);

    $inv->reserve($this->variant->id, $this->warehouse->id, 3, 'expiring-cart', ttlMinutes: -1);
    expect($inv->available($this->variant->id, $this->warehouse->id))->toBe($before - 3);

    $inv->releaseExpired();
    expect($inv->available($this->variant->id, $this->warehouse->id))->toBe($before);
});

it('prevents overselling under concurrent checkout attempts', function () {
    $inv = app(InventoryService::class);
    // Force stock to exactly 5.
    $available = $inv->available($this->variant->id, $this->warehouse->id);
    if ($available !== 5) {
        $inv->record($this->variant->id, $this->warehouse->id, 5 - $available, 'adjustment', reason: 'fix for test');
    }

    $succeeded = 0;
    $failed = 0;
    foreach (range(1, 10) as $i) {
        try {
            $inv->reserve($this->variant->id, $this->warehouse->id, 1, "cart-$i");
            $succeeded++;
        } catch (RuntimeException) {
            $failed++;
        }
    }

    expect($succeeded)->toBe(5)->and($failed)->toBe(5);
});

it('completes a POS sale, deducts stock and records payment', function () {
    $pos = app(PosService::class);
    $inv = app(InventoryService::class);
    $cashier = User::where('email', 'cashier@queensons.com')->first();
    $register = Register::first();
    // Sell from the register's own branch warehouse, and make sure it holds stock
    // (seeded stock levels are random, so this test was intermittently failing).
    $warehouseId = $register->branch->warehouses()->value('id');
    $inv->record($this->variant->id, $warehouseId, 5, 'purchase_receipt');
    $before = $inv->available($this->variant->id, $warehouseId);

    $session = $pos->openSession($register->id, $cashier->id, 500000);
    $sale = $pos->completeSale($session, [['variant_id' => $this->variant->id, 'qty' => 1]], 'cash');

    expect($sale->status)->toBe('completed');
    expect($inv->available($this->variant->id, $warehouseId))->toBe($before - 1);
    expect($sale->payments()->where('status', 'verified')->count())->toBe(1);

    // Return the item: stock comes back.
    $return = $pos->processReturn($sale, $session, [
        ['pos_sale_item_id' => $sale->items->first()->id, 'qty' => 1],
    ], 'resellable', 'changed mind', $cashier->id);

    expect($return->refund_cents)->toBe($sale->items->first()->unit_price_cents);
    expect($inv->available($this->variant->id, $this->warehouse->id))->toBe($before);

    // Close the register: cash sale minus cash refund, variance zero when counted right.
    $expected = 500000 + $sale->total_cents - $return->refund_cents;
    $closed = $pos->closeSession($session, $expected);
    expect($closed->variance_cents)->toBe(0);
});

it('blocks a second open session on the same register', function () {
    $pos = app(PosService::class);
    $cashier = User::where('email', 'cashier@queensons.com')->first();
    $manager = User::where('email', 'manager@queensons.com')->first();
    $register = Register::first();

    $pos->openSession($register->id, $cashier->id, 0);
    $pos->openSession($register->id, $manager->id, 0);
})->throws(RuntimeException::class);

it('enforces order status transition rules', function () {
    $checkout = app(CheckoutService::class);
    $order = Order::create([
        'reference' => 'QNS-TEST-00001',
        'status' => 'paid',
        'payment_status' => 'paid',
        'subtotal_cents' => 1000,
        'total_cents' => 1000,
        'warehouse_id' => $this->warehouse->id,
    ]);

    $checkout->transition($order, 'confirmed');
    expect($order->fresh()->status)->toBe('confirmed');

    $checkout->transition($order, 'delivered');
})->throws(RuntimeException::class);

it('applies percentage coupons within validity rules', function () {
    $coupon = \App\Models\Coupon::where('code', 'KARIBU10')->first();
    expect($coupon->isValid(300000))->toBeTrue();   // above 2,000 KES min
    expect($coupon->isValid(100000))->toBeFalse();  // below min order
    expect($coupon->discountFor(300000))->toBe(30000);
});

it('serves the storefront, admin and POS entry pages', function () {
    $this->get('/')->assertOk();
    $this->get('/shop')->assertOk();
    $this->get('/product/'.Product::first()->slug)->assertOk();

    $admin = User::where('email', 'admin@queensons.com')->first();
    $this->actingAs($admin)->get('/admin')->assertOk();
    $this->actingAs($admin)->get('/admin/guide')->assertOk();
    $this->actingAs($admin)->get('/pos')->assertOk();

    $cashier = User::where('email', 'cashier@queensons.com')->first();
    $this->actingAs($cashier)->get('/admin')->assertForbidden();
    $this->actingAs($cashier)->get('/pos')->assertOk();
});
