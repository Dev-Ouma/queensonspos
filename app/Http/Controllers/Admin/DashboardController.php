<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $onlineToday = Order::where('payment_status', 'paid')->where('placed_at', '>=', $today)->sum('total_cents');
        $posToday = PosSale::where('created_at', '>=', $today)->where('status', '!=', 'voided')->sum('total_cents');
        $onlineMonth = Order::where('payment_status', 'paid')->where('placed_at', '>=', $monthStart)->sum('total_cents');
        $posMonth = PosSale::where('created_at', '>=', $monthStart)->where('status', '!=', 'voided')->sum('total_cents');

        $cogsMonth = OrderItem::whereHas('order', fn ($q) => $q->where('payment_status', 'paid')->where('placed_at', '>=', $monthStart))
            ->sum(DB::raw('cost_cents_snapshot * qty'))
            + PosSaleItem::whereHas('sale', fn ($q) => $q->where('created_at', '>=', $monthStart)->where('status', '!=', 'voided'))
                ->sum(DB::raw('cost_cents_snapshot * qty'));

        $paidOrders = Order::where('payment_status', 'paid')->where('placed_at', '>=', $monthStart);
        $orderCount = (clone $paidOrders)->count();
        $aov = $orderCount ? intdiv((clone $paidOrders)->sum('total_cents'), $orderCount) : 0;

        $topProducts = OrderItem::select('name_snapshot', DB::raw('SUM(qty) as units'), DB::raw('SUM(line_total_cents) as revenue'))
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
            ->groupBy('name_snapshot')->orderByDesc('units')->limit(5)->get();

        $lowStock = InventoryBalance::with('variant.product')
            ->whereRaw('(on_hand - reserved - damaged) <= 5')
            ->limit(8)->get()
            ->map(fn ($b) => [
                'product' => $b->variant->product->name,
                'sku' => $b->variant->sku,
                'available' => $b->available,
            ]);

        $last14 = collect(range(13, 0))->map(function ($d) {
            $day = now()->subDays($d)->startOfDay();
            $end = (clone $day)->endOfDay();

            return [
                'date' => $day->format('d M'),
                'online' => Order::where('payment_status', 'paid')->whereBetween('placed_at', [$day, $end])->sum('total_cents') / 100,
                'pos' => PosSale::whereBetween('created_at', [$day, $end])->where('status', '!=', 'voided')->sum('total_cents') / 100,
            ];
        });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'sales_today_cents' => $onlineToday + $posToday,
                'sales_month_cents' => $onlineMonth + $posMonth,
                'gross_profit_month_cents' => ($onlineMonth + $posMonth) - $cogsMonth,
                'orders_month' => $orderCount,
                'aov_cents' => $aov,
                'customers' => Customer::count(),
                'products' => Product::count(),
                'pending_orders' => Order::whereIn('status', ['paid', 'confirmed', 'processing'])->count(),
            ],
            'salesChart' => $last14,
            'topProducts' => $topProducts,
            'lowStock' => $lowStock,
        ]);
    }
}
