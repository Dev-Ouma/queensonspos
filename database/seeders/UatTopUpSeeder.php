<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Coupon;
use App\Models\Delivery;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\PosCashMovement;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosSale;
use App\Models\Refund;
use App\Models\Supplier;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Widens demo data so every admin screen shows multiple rows,
 * every status filter has at least one hit, and empty states never show in UAT.
 * Idempotent: each block skips when its marker rows already exist.
 */
class UatTopUpSeeder extends Seeder
{
    public function run(): void
    {
        $this->brands();
        $this->coupons();
        $this->suppliers();
        $this->refunds();
        $this->posReturnsAndCash();
        $this->tickets();
        $this->failedDelivery();
        $this->pendingExpenses();
    }

    private function brands(): void
    {
        foreach (['Queensons Label', 'Nairobi Atelier', 'Savanna Luxe', 'Coast Breeze', 'Urban Siti'] as $name) {
            Brand::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'active' => true]);
        }
    }

    private function coupons(): void
    {
        $coupons = [
            ['code' => 'FLAT500', 'type' => 'fixed', 'value' => 50000, 'min_order_cents' => 300000, 'max_uses' => 100, 'used_count' => 12, 'active' => true],
            ['code' => 'FREESHIP', 'type' => 'free_delivery', 'value' => 0, 'min_order_cents' => 500000, 'max_uses' => null, 'used_count' => 4, 'active' => true],
            ['code' => 'VIP20', 'type' => 'percent', 'value' => 20, 'min_order_cents' => 1000000, 'max_uses' => 50, 'used_count' => 50, 'active' => true],
            ['code' => 'EASTER15', 'type' => 'percent', 'value' => 15, 'min_order_cents' => 0, 'max_uses' => 200, 'used_count' => 87,
                'starts_at' => now()->subMonths(8), 'ends_at' => now()->subMonths(7), 'active' => false],
        ];
        foreach ($coupons as $c) {
            Coupon::firstOrCreate(['code' => $c['code']], $c);
        }
    }

    private function suppliers(): void
    {
        $suppliers = [
            ['name' => 'Eastleigh Textiles Ltd', 'contact_person' => 'Abdi Hassan', 'phone' => '+254711000111', 'email' => 'sales@eastleightextiles.co.ke', 'address' => 'General Waruinge St, Nairobi', 'payment_terms' => 'Net 30', 'active' => true],
            ['name' => 'Mombasa Fabrics Co', 'contact_person' => 'Amina Said', 'phone' => '+254722000222', 'email' => 'orders@mombasafabrics.co.ke', 'address' => 'Nkrumah Rd, Mombasa', 'payment_terms' => 'Net 14', 'active' => true],
            ['name' => 'Guangzhou Fashion Export', 'contact_person' => 'Li Wei', 'phone' => '+8613800138000', 'email' => 'export@gzfashion.cn', 'address' => 'Baiyun District, Guangzhou', 'payment_terms' => 'Prepaid', 'active' => false],
        ];
        foreach ($suppliers as $s) {
            Supplier::firstOrCreate(['email' => $s['email']], $s);
        }
    }

    private function refunds(): void
    {
        if (Refund::count() >= 3) {
            return;
        }
        $finance = User::where('email', 'finance@queensons.com')->first();
        $payments = Payment::where('status', 'verified')->orderByDesc('id')->limit(2)->get();
        $specs = [
            ['reference' => 'REF-DEMO-0002', 'reason' => 'Order cancelled before dispatch', 'status' => 'pending', 'approved_by' => null],
            ['reference' => 'REF-DEMO-0003', 'reason' => 'Damaged item claim — rejected after inspection', 'status' => 'rejected', 'approved_by' => $finance?->id],
        ];
        foreach ($specs as $i => $spec) {
            $p = $payments[$i] ?? null;
            if (! $p || Refund::where('reference', $spec['reference'])->exists()) {
                continue;
            }
            Refund::create($spec + ['payment_id' => $p->id, 'amount_cents' => intdiv($p->amount_cents, 3), 'created_at' => now()->subDays(2 - $i)]);
        }
    }

    private function posReturnsAndCash(): void
    {
        $cashier = User::where('email', 'cashier@queensons.com')->first() ?? User::first();

        if (PosReturn::count() < 2) {
            $sale = PosSale::has('items')->whereDoesntHave('returns')->orderByDesc('id')->first();
            if ($sale) {
                $item = $sale->items->first();
                $return = PosReturn::create([
                    'reference' => 'RET-DEMO-0002',
                    'pos_sale_id' => $sale->id,
                    'pos_session_id' => $sale->pos_session_id,
                    'refund_cents' => $item->line_total_cents ?? intdiv($sale->total_cents, 2),
                    'reason' => 'Wrong size exchanged',
                    'condition' => 'damaged',
                    'user_id' => $cashier?->id,
                ]);
                PosReturnItem::create(['pos_return_id' => $return->id, 'pos_sale_item_id' => $item->id, 'qty' => 1]);
            }
        }

        if (PosCashMovement::count() < 6) {
            $sessions = \App\Models\PosSession::orderByDesc('id')->limit(2)->get();
            foreach ($sessions as $s) {
                PosCashMovement::firstOrCreate(
                    ['pos_session_id' => $s->id, 'type' => 'cash_in', 'reason' => 'Float top-up from safe'],
                    ['amount_cents' => 500000, 'user_id' => $cashier?->id, 'created_at' => now()->subHours(5)]
                );
                PosCashMovement::firstOrCreate(
                    ['pos_session_id' => $s->id, 'type' => 'cash_out', 'reason' => 'Petty cash — cleaning supplies'],
                    ['amount_cents' => 120000, 'user_id' => $cashier?->id, 'created_at' => now()->subHours(3)]
                );
            }
        }
    }

    private function tickets(): void
    {
        if (SupportTicket::count() >= 6) {
            return;
        }
        $staff = User::where('email', 'admin@queensons.com')->first();
        $customers = \App\Models\Customer::orderBy('id')->get();
        $specs = [
            ['subject' => 'Coupon code VIP20 not applying at checkout', 'status' => 'open',
                'messages' => [['customer', 'I entered VIP20 but the discount does not show on my cart total.']]],
            ['subject' => 'Request to change delivery address on order', 'status' => 'in_progress',
                'messages' => [['customer', 'Please deliver to my office in Westlands instead of home.'], ['staff', 'Noted — updating with the courier, will confirm shortly.']]],
            ['subject' => 'Received wrong colour blazer', 'status' => 'closed',
                'messages' => [['customer', 'Ordered navy, received black.'], ['staff', 'Exchange arranged and completed. Closing this ticket.']]],
        ];
        foreach ($specs as $i => $spec) {
            $customer = $customers[$i % max($customers->count(), 1)] ?? null;
            if (! $customer) {
                continue;
            }
            $ref = 'TCK-UAT-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
            if (SupportTicket::where('reference', $ref)->exists()) {
                continue;
            }
            $ticket = SupportTicket::create([
                'reference' => $ref, 'customer_id' => $customer->id, 'subject' => $spec['subject'],
                'status' => $spec['status'], 'assigned_to' => $spec['status'] === 'open' ? null : $staff?->id,
                'created_at' => now()->subDays(3 - $i),
            ]);
            foreach ($spec['messages'] as [$sender, $body]) {
                TicketMessage::create([
                    'support_ticket_id' => $ticket->id,
                    'sender_type' => $sender,
                    'sender_id' => $sender === 'staff' ? $staff?->id : $customer->id,
                    'body' => $body,
                ]);
            }
        }
    }

    private function failedDelivery(): void
    {
        if (Delivery::where('status', 'failed')->exists()) {
            return;
        }
        $delivery = Delivery::where('status', 'pending')->orderByDesc('id')->first();
        $delivery?->update([
            'status' => 'failed',
            'failure_reason' => 'Customer unreachable at delivery address',
            'dispatched_at' => now()->subDay(),
        ]);
    }

    private function pendingExpenses(): void
    {
        if (Expense::where('status', '!=', 'approved')->exists()) {
            return;
        }
        $manager = User::where('email', 'manager@queensons.com')->first() ?? User::first();
        $category = ExpenseCategory::first();
        $branch = \App\Models\Branch::first();
        if (! $category || ! $branch) {
            return;
        }
        foreach ([
            ['description' => 'Mannequin restock for window display', 'amount_cents' => 1850000, 'status' => 'pending'],
            ['description' => 'Signage repair — duplicate claim', 'amount_cents' => 450000, 'status' => 'rejected'],
        ] as $i => $e) {
            Expense::firstOrCreate(
                ['description' => $e['description']],
                $e + ['expense_category_id' => $category->id, 'branch_id' => $branch->id,
                    'incurred_on' => now()->subDays(4 - $i)->toDateString(), 'created_by' => $manager?->id]
            );
        }
    }
}
