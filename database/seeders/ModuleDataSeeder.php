<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Delivery;
use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PosCashMovement;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Refund;
use App\Models\Register;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Supplier;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WishlistItem;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ModuleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->branches();
        $this->customers();
        $this->reviewsAndWishlists();
        $this->procurement();
        $this->stockTransfers();
        $this->deliveries();
        $this->refundsAndReturns();
        $this->supportTickets();
        $this->cashMovements();
    }

    private function branches(): void
    {
        $branch = Branch::firstOrCreate(['code' => 'WST'], [
            'name' => 'QUEENSONS Westlands', 'address' => 'Westlands, Nairobi', 'phone' => '+254700000002',
        ]);
        $wh = Warehouse::firstOrCreate(['code' => 'WST-WH'], ['branch_id' => $branch->id, 'name' => 'Westlands Store Room']);
        Register::firstOrCreate(['branch_id' => $branch->id, 'name' => 'Register 1']);

        // Opening stock for a handful of variants at the new warehouse.
        $inv = app(InventoryService::class);
        foreach (ProductVariant::inRandomOrder()->limit(25)->get() as $variant) {
            if (! $variant->balances()->where('warehouse_id', $wh->id)->exists()) {
                $inv->record($variant->id, $wh->id, random_int(3, 15), 'opening_balance', reason: 'Westlands opening stock');
            }
        }
    }

    private function customers(): void
    {
        $customers = [
            ['Achieng Odhiambo', 'achieng@example.com', '+254722111001'],
            ['Wanjiru Kamau', 'wanjiru@example.com', '+254722111002'],
            ['Fatuma Hassan', 'fatuma@example.com', '+254722111003'],
            ['Njeri Mwangi', 'njeri@example.com', '+254722111004'],
            ['Akinyi Otieno', 'akinyi@example.com', '+254722111005'],
            ['Chebet Kiprop', 'chebet@example.com', '+254722111006'],
        ];
        foreach ($customers as [$name, $email, $phone]) {
            $c = Customer::firstOrCreate(['email' => $email], [
                'name' => $name, 'phone' => $phone, 'password' => Hash::make('password'),
                'email_verified_at' => now(), 'loyalty_points' => random_int(10, 250),
            ]);
            if (! $c->addresses()->exists()) {
                CustomerAddress::create([
                    'customer_id' => $c->id, 'label' => 'Home',
                    'line1' => fake_street(), 'city' => ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru'][random_int(0, 3)],
                    'phone' => $phone, 'is_default' => true,
                ]);
            }
            if ($c->loyalty_points > 0 && ! LoyaltyTransaction::where('customer_id', $c->id)->exists()) {
                LoyaltyTransaction::create([
                    'customer_id' => $c->id, 'points' => $c->loyalty_points, 'reason' => 'demo_seed_balance',
                ]);
            }
        }
    }

    private function reviewsAndWishlists(): void
    {
        if (ProductReview::count() > 5) {
            return;
        }
        $texts = [
            [5, 'Absolutely love it! The fabric quality is way above the price. Fits true to size.'],
            [4, 'Beautiful piece, delivery was quick. Slightly long for my height but easy to adjust.'],
            [5, 'My third order from QUEENSONS. Never disappoints. Office wear that actually lasts.'],
            [3, 'Nice colour but the material felt thinner than expected. Still decent value.'],
            [5, 'Received so many compliments at the wedding. Will definitely shop again!'],
            [4, 'Good quality and true to the photos. Sizing chart was accurate.'],
            [2, 'Colour faded slightly after the first wash despite following care instructions.'],
            [5, 'Perfect fit, elegant finish. The gold accents are stunning in person.'],
        ];
        $customers = Customer::pluck('id');
        $products = Product::published()->inRandomOrder()->limit(8)->get();
        foreach ($products as $i => $product) {
            [$rating, $body] = $texts[$i % count($texts)];
            ProductReview::create([
                'product_id' => $product->id,
                'customer_id' => $customers->random(),
                'rating' => $rating,
                'body' => $body,
                'status' => $i < 6 ? 'published' : 'pending',
            ]);
        }
        foreach (Customer::inRandomOrder()->limit(4)->get() as $c) {
            foreach (Product::published()->inRandomOrder()->limit(random_int(2, 4))->get() as $p) {
                WishlistItem::firstOrCreate(['customer_id' => $c->id, 'product_id' => $p->id]);
            }
        }
    }

    private function procurement(): void
    {
        if (PurchaseOrder::count() > 0) {
            return;
        }
        $inv = app(InventoryService::class);
        $admin = User::where('email', 'admin@queensons.com')->first();
        $whId = Warehouse::where('code', 'MAIN')->value('id');
        $suppliers = Supplier::all();

        // PO 1: fully received.
        $po1 = PurchaseOrder::create([
            'reference' => 'PO-DEMO-0001', 'supplier_id' => $suppliers[0]->id, 'warehouse_id' => $whId,
            'status' => 'received', 'created_by' => $admin->id, 'approved_by' => $admin->id,
            'created_at' => now()->subDays(9),
        ]);
        $grn = GoodsReceivedNote::create([
            'reference' => 'GRN-DEMO-0001', 'purchase_order_id' => $po1->id,
            'supplier_delivery_ref' => 'MIL-4471', 'received_by' => $admin->id, 'created_at' => now()->subDays(7),
        ]);
        $total = 0;
        foreach (ProductVariant::inRandomOrder()->limit(4)->get() as $variant) {
            $qty = random_int(10, 25);
            $cost = (int) ($variant->cost_cents * 0.95);
            $item = PurchaseOrderItem::create([
                'purchase_order_id' => $po1->id, 'variant_id' => $variant->id,
                'qty_ordered' => $qty, 'qty_received' => $qty, 'unit_cost_cents' => $cost,
            ]);
            GrnItem::create([
                'goods_received_note_id' => $grn->id, 'purchase_order_item_id' => $item->id,
                'qty_received' => $qty, 'qty_damaged' => 0,
            ]);
            $inv->record($variant->id, $whId, $qty, 'purchase_receipt', GoodsReceivedNote::class, $grn->id, $admin->id);
            $total += $cost * $qty;
        }
        $po1->update(['total_cents' => $total]);

        // PO 2: approved, awaiting delivery.
        $po2 = PurchaseOrder::create([
            'reference' => 'PO-DEMO-0002', 'supplier_id' => $suppliers[1]->id, 'warehouse_id' => $whId,
            'status' => 'approved', 'created_by' => $admin->id, 'approved_by' => $admin->id,
            'created_at' => now()->subDays(2),
        ]);
        $total = 0;
        foreach (ProductVariant::inRandomOrder()->limit(3)->get() as $variant) {
            $qty = random_int(15, 30);
            PurchaseOrderItem::create([
                'purchase_order_id' => $po2->id, 'variant_id' => $variant->id,
                'qty_ordered' => $qty, 'unit_cost_cents' => $variant->cost_cents,
            ]);
            $total += $variant->cost_cents * $qty;
        }
        $po2->update(['total_cents' => $total]);
    }

    private function stockTransfers(): void
    {
        if (StockTransfer::count() > 0) {
            return;
        }
        $inv = app(InventoryService::class);
        $admin = User::where('email', 'inventory@queensons.com')->first() ?? User::first();
        $main = Warehouse::where('code', 'MAIN')->first();
        $west = Warehouse::where('code', 'WST-WH')->first();

        // Completed transfer MAIN -> Westlands.
        $t1 = StockTransfer::create([
            'reference' => 'TRF-DEMO-0001', 'from_warehouse_id' => $main->id, 'to_warehouse_id' => $west->id,
            'status' => 'received', 'created_by' => $admin->id, 'created_at' => now()->subDays(5),
        ]);
        foreach (ProductVariant::whereHas('balances', fn ($q) => $q->where('warehouse_id', $main->id)->where('on_hand', '>', 6))->inRandomOrder()->limit(3)->get() as $variant) {
            $qty = 3;
            StockTransferItem::create([
                'stock_transfer_id' => $t1->id, 'variant_id' => $variant->id, 'qty' => $qty, 'received_qty' => $qty,
            ]);
            $inv->record($variant->id, $main->id, -$qty, 'transfer_out', StockTransfer::class, $t1->id, $admin->id);
            $inv->record($variant->id, $west->id, $qty, 'transfer_in', StockTransfer::class, $t1->id, $admin->id);
        }

        // Pending transfer awaiting dispatch.
        $t2 = StockTransfer::create([
            'reference' => 'TRF-DEMO-0002', 'from_warehouse_id' => $main->id, 'to_warehouse_id' => $west->id,
            'status' => 'approved', 'created_by' => $admin->id,
        ]);
        foreach (ProductVariant::inRandomOrder()->limit(2)->get() as $variant) {
            StockTransferItem::create(['stock_transfer_id' => $t2->id, 'variant_id' => $variant->id, 'qty' => 4]);
        }
    }

    private function deliveries(): void
    {
        if (Delivery::count() > 0) {
            return;
        }
        $couriers = ['Sendy Rider', 'G4S Courier', 'In-house Rider', 'Wells Fargo'];
        $orders = Order::where('payment_status', 'paid')->orderBy('placed_at')->limit(12)->get();
        foreach ($orders as $i => $order) {
            $status = $i < 8 ? 'delivered' : ($i < 10 ? 'dispatched' : 'pending');
            Delivery::create([
                'order_id' => $order->id,
                'courier' => $couriers[$i % 4],
                'tracking_no' => 'TRK-'.strtoupper(Str::random(8)),
                'status' => $status,
                'dispatched_at' => $status !== 'pending' ? $order->placed_at?->addHours(20) : null,
                'delivered_at' => $status === 'delivered' ? $order->placed_at?->addHours(42) : null,
            ]);
        }
    }

    private function refundsAndReturns(): void
    {
        if (Refund::count() > 0) {
            return;
        }
        $finance = User::where('email', 'finance@queensons.com')->first();

        // One online refund (approved, partial).
        $payment = Payment::where('subject_type', Order::class)->where('status', 'verified')->first();
        if ($payment) {
            Refund::create([
                'reference' => 'REF-DEMO-0001', 'payment_id' => $payment->id,
                'amount_cents' => intdiv($payment->amount_cents, 2),
                'reason' => 'One item returned — wrong size', 'status' => 'completed',
                'approved_by' => $finance?->id, 'created_at' => now()->subDays(3),
            ]);
        }

        // One POS return against a demo sale.
        $sale = PosSale::where('reference', 'like', 'POS-DEMO-%')->with('items')->first();
        if ($sale && $sale->items->isNotEmpty()) {
            $item = $sale->items->first();
            $inv = app(InventoryService::class);
            $whId = Warehouse::where('code', 'MAIN')->value('id');
            $cashier = User::where('email', 'cashier@queensons.com')->first();
            $return = PosReturn::create([
                'reference' => 'RET-DEMO-0001', 'pos_sale_id' => $sale->id, 'pos_session_id' => $sale->pos_session_id,
                'refund_cents' => $item->unit_price_cents, 'reason' => 'Customer changed mind',
                'condition' => 'resellable', 'user_id' => $cashier->id, 'created_at' => now()->subDays(4),
            ]);
            PosReturnItem::create(['pos_return_id' => $return->id, 'pos_sale_item_id' => $item->id, 'qty' => 1]);
            $inv->record($item->variant_id, $whId, 1, 'customer_return', PosReturn::class, $return->id, $cashier->id);
            $sale->update(['status' => 'partially_refunded']);
        }
    }

    private function supportTickets(): void
    {
        if (SupportTicket::count() > 0) {
            return;
        }
        $support = User::where('email', 'manager@queensons.com')->first();
        $cases = [
            ['Order not yet delivered', 'open', [
                ['customer', 'Hi, I placed order three days ago and it still shows processing. When will it arrive?'],
            ]],
            ['Wrong size received', 'in_progress', [
                ['customer', 'I ordered size M but received size S. How do I exchange it?'],
                ['staff', 'So sorry about that! Please bring it to any branch or we can arrange a rider pickup. The replacement M is reserved for you.'],
            ]],
            ['Refund status enquiry', 'resolved', [
                ['customer', 'I returned a dress last week. When will my refund be processed?'],
                ['staff', 'Your refund was approved and sent to your M-Pesa today. Reference REF-DEMO-0001.'],
                ['customer', 'Received it, thank you!'],
            ]],
        ];
        foreach ($cases as $i => [$subject, $status, $messages]) {
            $customer = Customer::inRandomOrder()->first();
            $ticket = SupportTicket::create([
                'reference' => 'TKT-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id, 'subject' => $subject, 'status' => $status,
                'assigned_to' => $status === 'open' ? null : $support?->id,
                'created_at' => now()->subDays(6 - $i),
            ]);
            foreach ($messages as [$senderType, $body]) {
                TicketMessage::create([
                    'support_ticket_id' => $ticket->id,
                    'sender_type' => $senderType,
                    'sender_id' => $senderType === 'customer' ? $customer->id : $support?->id ?? 1,
                    'body' => $body,
                ]);
            }
        }
    }

    private function cashMovements(): void
    {
        if (PosCashMovement::count() > 0) {
            return;
        }
        $session = PosSession::latest('opened_at')->first();
        $cashier = User::where('email', 'cashier@queensons.com')->first();
        if ($session) {
            PosCashMovement::create([
                'pos_session_id' => $session->id, 'type' => 'cash_in',
                'amount_cents' => 200000, 'reason' => 'Float top-up from safe', 'user_id' => $cashier->id,
            ]);
            PosCashMovement::create([
                'pos_session_id' => $session->id, 'type' => 'cash_out',
                'amount_cents' => 150000, 'reason' => 'Banking run — midday deposit', 'user_id' => $cashier->id,
            ]);
        }
    }
}

if (! function_exists('fake_street')) {
    function fake_street(): string
    {
        $roads = ['Moi Avenue', 'Kenyatta Road', 'Ngong Road', 'Waiyaki Way', 'Thika Road', 'Mama Ngina Street'];

        return 'House '.random_int(1, 99).', '.$roads[random_int(0, count($roads) - 1)];
    }
}
