<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);

        $online = Order::where('payment_status', 'paid')->whereBetween('placed_at', [$from, $to]);
        $pos = PosSale::where('status', '!=', 'voided')->whereBetween('created_at', [$from, $to]);

        $onlineRevenue = (clone $online)->sum('total_cents');
        $posRevenue = (clone $pos)->sum('total_cents');

        $cogs = OrderItem::whereHas('order', fn ($q) => $q->where('payment_status', 'paid')->whereBetween('placed_at', [$from, $to]))
            ->sum(DB::raw('cost_cents_snapshot * qty'))
            + PosSaleItem::whereHas('sale', fn ($q) => $q->where('status', '!=', 'voided')->whereBetween('created_at', [$from, $to]))
                ->sum(DB::raw('cost_cents_snapshot * qty'));

        $expenses = Expense::where('status', 'approved')->whereBetween('incurred_on', [$from, $to])->sum('amount_cents');

        $byProduct = OrderItem::select('name_snapshot',
            DB::raw('SUM(qty) as units'),
            DB::raw('SUM(line_total_cents) as revenue_cents'),
            DB::raw('SUM(cost_cents_snapshot * qty) as cogs_cents'))
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid')->whereBetween('placed_at', [$from, $to]))
            ->groupBy('name_snapshot')->orderByDesc('revenue_cents')->limit(20)->get();

        $byMethod = \App\Models\Payment::select('method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount_cents) as total_cents'))
            ->where('status', 'verified')->whereBetween('created_at', [$from, $to])
            ->groupBy('method')->get();

        return Inertia::render('Admin/Reports', [
            'summary' => [
                'online_revenue_cents' => $onlineRevenue,
                'pos_revenue_cents' => $posRevenue,
                'total_revenue_cents' => $onlineRevenue + $posRevenue,
                'cogs_cents' => $cogs,
                'gross_profit_cents' => $onlineRevenue + $posRevenue - $cogs,
                'expenses_cents' => $expenses,
                'net_result_cents' => $onlineRevenue + $posRevenue - $cogs - $expenses,
                'online_orders' => (clone $online)->count(),
                'pos_sales' => (clone $pos)->count(),
            ],
            'byProduct' => $byProduct,
            'byMethod' => $byMethod,
            'filters' => ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);

        $rows = OrderItem::with('order')
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid')->whereBetween('placed_at', [$from, $to]))
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order', 'Date', 'Product', 'SKU', 'Qty', 'Unit Price', 'Line Total']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->order->reference,
                    $r->order->placed_at?->format('Y-m-d H:i'),
                    $r->name_snapshot,
                    $r->sku_snapshot,
                    $r->qty,
                    number_format($r->unit_price_cents / 100, 2, '.', ''),
                    number_format($r->line_total_cents / 100, 2, '.', ''),
                ]);
            }
            fclose($out);
        }, 'queensons-sales-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function range(Request $request): array
    {
        $from = $request->filled('from') ? now()->parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? now()->parse($request->to)->endOfDay() : now()->endOfDay();

        return [$from, $to];
    }
}
