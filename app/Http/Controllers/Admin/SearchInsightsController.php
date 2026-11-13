<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use App\Models\SearchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/** Admin → Search: what shoppers look for, what finds nothing, and saved searches. */
class SearchInsightsController extends Controller
{
    public function index(Request $request)
    {
        $days = in_array((int) $request->days, [7, 30, 90], true) ? (int) $request->days : 30;
        $since = now()->subDays($days);
        $base = fn () => SearchLog::where('created_at', '>=', $since);

        $top = $base()->select(DB::raw('LOWER(term) as term'), DB::raw('COUNT(*) as searches'), DB::raw('ROUND(AVG(results)) as avg_results'))
            ->groupBy(DB::raw('LOWER(term)'))->orderByDesc('searches')->limit(15)->get();

        $zero = $base()->where('results', 0)->select(DB::raw('LOWER(term) as term'), DB::raw('COUNT(*) as searches'), DB::raw('MAX(created_at) as last_at'))
            ->groupBy(DB::raw('LOWER(term)'))->orderByDesc('searches')->limit(15)->get();

        $saved = SavedSearch::select('label', DB::raw('COUNT(*) as customers'))
            ->groupBy('label')->orderByDesc('customers')->limit(10)->get();

        $total = $base()->count();

        return Inertia::render('Admin/SearchInsights', [
            'days' => $days,
            'stats' => [
                'searches' => $total,
                'shoppers' => $base()->whereNotNull('customer_id')->distinct('customer_id')->count('customer_id'),
                'zero_rate' => $total ? round($base()->where('results', 0)->count() / $total * 100) : 0,
                'ai_rate' => $total ? round($base()->where('ai', true)->count() / $total * 100) : 0,
                'saved' => SavedSearch::count(),
            ],
            'top' => $top,
            'zero' => $zero,
            'saved' => $saved,
            'recent' => $base()->latest('created_at')->limit(20)->get(['term', 'filters', 'results', 'ai', 'created_at']),
        ]);
    }

    /** Download every search in the chosen period as CSV. */
    public function export(Request $request)
    {
        $days = in_array((int) $request->days, [7, 30, 90], true) ? (int) $request->days : 30;
        $rows = SearchLog::where('created_at', '>=', now()->subDays($days))->latest('created_at')->cursor();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Search', 'Results', 'Read by', 'Filters']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->created_at->format('Y-m-d H:i'), $r->term, $r->results, $r->ai ? 'AI' : 'Rules',
                    collect($r->filters ?? [])->map(fn ($v, $k) => "$k=$v")->implode('; '),
                ]);
            }
            fclose($out);
        }, "searches-last-{$days}-days.csv", ['Content-Type' => 'text/csv']);
    }
}
