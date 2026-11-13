<?php

use App\Models\Delivery;
use App\Models\GoodsReceivedNote;
use App\Models\PosReturn;
use App\Models\ProductReview;
use App\Models\PurchaseOrder;
use App\Models\Refund;
use App\Models\StockTransfer;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WishlistItem;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->admin = User::where('email', 'admin@queensons.com')->first();
});

it('seeds demo data for every module', function () {
    expect(ProductReview::count())->toBeGreaterThan(0)
        ->and(WishlistItem::count())->toBeGreaterThan(0)
        ->and(PurchaseOrder::count())->toBeGreaterThanOrEqual(2)
        ->and(GoodsReceivedNote::count())->toBeGreaterThan(0)
        ->and(StockTransfer::count())->toBeGreaterThanOrEqual(2)
        ->and(Delivery::count())->toBeGreaterThan(0)
        ->and(Refund::count())->toBeGreaterThan(0)
        ->and(PosReturn::count())->toBeGreaterThan(0)
        ->and(SupportTicket::count())->toBeGreaterThanOrEqual(3)
        ->and(Warehouse::count())->toBeGreaterThanOrEqual(2);
});

it('keeps the inventory ledger consistent with balances after all seeding', function () {
    $mismatches = \Illuminate\Support\Facades\DB::select(<<<'SQL'
        SELECT b.variant_id, b.warehouse_id, b.on_hand + b.damaged AS balance,
               COALESCE(SUM(t.qty_delta), 0) AS ledger
        FROM inventory_balances b
        LEFT JOIN inventory_transactions t
            ON t.variant_id = b.variant_id AND t.warehouse_id = b.warehouse_id
        GROUP BY b.variant_id, b.warehouse_id, b.on_hand, b.damaged
        HAVING b.on_hand + b.damaged <> COALESCE(SUM(t.qty_delta), 0)
    SQL);

    expect($mismatches)->toBeEmpty();
});

it('renders every admin module page with seeded data', function () {
    $pages = [
        '/admin', '/admin/products', '/admin/categories', '/admin/inventory', '/admin/inventory/ledger',
        '/admin/transfers', '/admin/deliveries', '/admin/orders', '/admin/customers', '/admin/tickets', '/admin/suppliers',
        '/admin/purchases', '/admin/expenses', '/admin/reports', '/admin/coupons', '/admin/reviews',
        '/admin/cms', '/admin/users', '/admin/settings', '/admin/audit', '/admin/guide',
    ];
    foreach ($pages as $page) {
        $this->actingAs($this->admin)->get($page)->assertOk();
    }
});

it('lets staff reply to a support ticket and update its status', function () {
    $ticket = SupportTicket::where('status', 'open')->first();

    $this->actingAs($this->admin)
        ->post("/admin/tickets/{$ticket->id}/reply", [
            'body' => 'We are checking with the courier and will update you shortly.',
            'status' => 'in_progress',
        ])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe('in_progress')
        ->and($ticket->messages()->where('sender_type', 'staff')->exists())->toBeTrue();
});

it('shows published reviews on the storefront product page', function () {
    $review = ProductReview::where('status', 'published')->with('product')->first();

    $this->get('/product/'.$review->product->slug)
        ->assertOk()
        ->assertSee($review->body, false);
});

it('dispatches a pending delivery with courier and tracking details', function () {
    $delivery = \App\Models\Delivery::where('status', 'pending')->first();

    $this->actingAs($this->admin)
        ->patch("/admin/deliveries/{$delivery->id}", [
            'status' => 'dispatched',
            'courier' => 'In-house Rider',
            'tracking_no' => 'TRK-TEST0001',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $fresh = $delivery->fresh();
    expect($fresh->status)->toBe('dispatched')
        ->and($fresh->tracking_no)->toBe('TRK-TEST0001')
        ->and($fresh->dispatched_at)->not->toBeNull();
});

it('rejects an invalid delivery status transition', function () {
    $delivery = \App\Models\Delivery::where('status', 'delivered')->first();

    $this->actingAs($this->admin)
        ->patch("/admin/deliveries/{$delivery->id}", ['status' => 'pending'])
        ->assertSessionHasErrors('status');

    expect($delivery->fresh()->status)->toBe('delivered');
});

it('lets admin publish storefront content that renders on the website', function () {
    $content = \App\Support\StorefrontContent::defaults();
    $content['announcements'][0] = 'UAT announcement karibu sana';
    $content['flash_sale']['title'] = 'UAT Flash Edit';
    $content['testimonials'][0]['quote'] = 'UAT quote from a very happy shopper.';

    $this->actingAs($this->admin)
        ->post('/admin/cms/storefront', $content)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->get('/')
        ->assertOk()
        ->assertSee('UAT announcement karibu sana', false)
        ->assertSee('UAT Flash Edit', false)
        ->assertSee('UAT quote from a very happy shopper.', false);
});

it('rejects storefront content with an invalid hero image URL', function () {
    $content = \App\Support\StorefrontContent::defaults();
    $content['hero_slides'][0]['img'] = 'not-a-url';

    $this->actingAs($this->admin)
        ->post('/admin/cms/storefront', $content)
        ->assertSessionHasErrors('hero_slides.0.img');
});
