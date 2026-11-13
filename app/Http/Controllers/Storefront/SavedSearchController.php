<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SavedSearchController extends Controller
{
    public function index()
    {
        $searches = SavedSearch::where('customer_id', Auth::guard('customer')->id())->latest()->get()
            ->map(fn ($s) => ['id' => $s->id, 'label' => $s->label, 'url' => $s->url(), 'saved_at' => $s->created_at->format('d M Y')]);

        return Inertia::render('Account/SavedSearches', ['searches' => $searches]);
    }

    /** Save the current /shop filters; saving the same filters twice removes them (toggle). */
    public function toggle(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'collection' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'size' => 'nullable|string|max:20',
            'sort' => 'nullable|string|max:20',
            'min_price' => 'nullable|integer|min:0',
            'max_price' => 'nullable|integer|min:0',
        ]);

        $filters = SavedSearch::normalise($request->only(SavedSearch::KEYS));
        $hash = SavedSearch::hashFor($filters);
        $customerId = Auth::guard('customer')->id();

        $existing = SavedSearch::where('customer_id', $customerId)->where('filters_hash', $hash)->first();
        if ($existing) {
            $existing->delete();

            return back()->with('success', 'Search removed from your saved searches.');
        }

        SavedSearch::create([
            'customer_id' => $customerId,
            'label' => $this->label($filters),
            'filters' => $filters,
            'filters_hash' => $hash,
        ]);

        return back()->with('success', 'Search saved. Find it in My QUEENSONS.');
    }

    public function destroy(SavedSearch $savedSearch)
    {
        abort_unless($savedSearch->customer_id === Auth::guard('customer')->id(), 404);
        $savedSearch->delete();

        return back();
    }

    /** Human label such as: "dress" in Dresses · Size M · Up to KES 5,000 */
    private function label(array $f): string
    {
        $parts = [];
        $category = isset($f['category']) ? Category::where('slug', $f['category'])->value('name') ?? $f['category'] : null;
        if (isset($f['q'])) {
            $parts[] = '"'.$f['q'].'"'.($category ? " in {$category}" : '');
        } elseif ($category) {
            $parts[] = $category;
        }
        if (isset($f['collection'])) $parts[] = ucwords(str_replace('-', ' ', $f['collection']));
        if (isset($f['color'])) $parts[] = $f['color'];
        if (isset($f['size'])) $parts[] = 'Size '.$f['size'];
        if (isset($f['min_price'])) $parts[] = 'From KES '.number_format($f['min_price']);
        if (isset($f['max_price'])) $parts[] = 'Up to KES '.number_format($f['max_price']);

        return mb_substr(implode(' · ', $parts) ?: 'All women\'s fashion', 0, 160);
    }
}
