<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\StockTransfer;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CrmController extends Controller
{
    public function tickets(Request $request)
    {
        $query = SupportTicket::with(['customer:id,name,email', 'messages'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/Tickets', [
            'tickets' => $query->paginate(15)->withQueryString()->through(fn ($t) => [
                'id' => $t->id,
                'reference' => $t->reference,
                'customer' => $t->customer->name,
                'email' => $t->customer->email,
                'subject' => $t->subject,
                'status' => $t->status,
                'created' => $t->created_at->format('d M Y H:i'),
                'messages' => $t->messages->map(fn ($m) => [
                    'sender' => $m->sender_type,
                    'body' => $m->body,
                    'at' => $m->created_at?->format('d M H:i'),
                ]),
            ]),
            'filters' => $request->only(['status']),
        ]);
    }

    public function ticketReply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'body' => 'required|string|max:2000',
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);
        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'staff',
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);
        $ticket->update(['status' => $data['status'], 'assigned_to' => $ticket->assigned_to ?? $request->user()->id]);

        return back()->with('success', 'Reply sent.');
    }

    public function deliveries(Request $request)
    {
        $query = Delivery::with(['order:id,reference,customer_id,guest_name,total_cents', 'order.customer:id,name'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/Deliveries', [
            'deliveries' => $query->paginate(15)->withQueryString()->through(fn ($d) => [
                'id' => $d->id,
                'order_ref' => $d->order->reference,
                'order_id' => $d->order->id,
                'customer' => $d->order->customer->name ?? $d->order->guest_name ?? 'Guest',
                'total_cents' => $d->order->total_cents,
                'courier' => $d->courier,
                'tracking_no' => $d->tracking_no,
                'status' => $d->status,
                'failure_reason' => $d->failure_reason,
                'dispatched_at' => $d->dispatched_at?->format('d M Y H:i'),
                'delivered_at' => $d->delivered_at?->format('d M Y H:i'),
            ]),
            'filters' => $request->only(['status']),
            'statusCounts' => Delivery::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function deliveryUpdate(Request $request, Delivery $delivery)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,dispatched,delivered,failed',
            'courier' => 'nullable|string|max:120',
            'tracking_no' => 'nullable|string|max:120',
            'failure_reason' => 'nullable|string|max:255|required_if:status,failed',
        ]);

        $allowed = [
            'pending' => ['pending', 'dispatched', 'failed'],
            'dispatched' => ['dispatched', 'delivered', 'failed'],
            'delivered' => ['delivered'],
            'failed' => ['failed', 'dispatched'],
        ];
        if (! in_array($data['status'], $allowed[$delivery->status], true)) {
            return back()->withErrors(['status' => "Cannot move delivery from {$delivery->status} to {$data['status']}."]);
        }

        $delivery->update([
            'status' => $data['status'],
            'courier' => $data['courier'] ?? $delivery->courier,
            'tracking_no' => $data['tracking_no'] ?? $delivery->tracking_no,
            'failure_reason' => $data['status'] === 'failed' ? $data['failure_reason'] : null,
            'dispatched_at' => $data['status'] === 'dispatched' ? ($delivery->dispatched_at ?? now()) : $delivery->dispatched_at,
            'delivered_at' => $data['status'] === 'delivered' ? ($delivery->delivered_at ?? now()) : null,
        ]);

        return back()->with('success', 'Delivery updated.');
    }

    public function transfers()
    {
        return Inertia::render('Admin/Transfers', [
            'transfers' => StockTransfer::with(['fromWarehouse:id,name', 'toWarehouse:id,name', 'items.variant.product'])
                ->latest()->paginate(15)->through(fn ($t) => [
                    'id' => $t->id,
                    'reference' => $t->reference,
                    'from' => $t->fromWarehouse->name,
                    'to' => $t->toWarehouse->name,
                    'status' => $t->status,
                    'created' => $t->created_at->format('d M Y'),
                    'items' => $t->items->map(fn ($i) => [
                        'product' => $i->variant->product->name,
                        'sku' => $i->variant->sku,
                        'qty' => $i->qty,
                        'received_qty' => $i->received_qty,
                    ]),
                ]),
        ]);
    }
}
