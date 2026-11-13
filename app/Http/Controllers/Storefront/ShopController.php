<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function home()
    {
        return Inertia::render('Storefront/Home', [
            'banners' => Banner::where('active', true)->orderBy('position')->get(),
            'featured' => $this->productCards(Product::published()->where('featured', true)->with('variants', 'images')->limit(8)->get()),
            'newArrivals' => $this->productCards(
                Collection::where('slug', 'new-arrivals')->first()?->products()->published()->with('variants', 'images')->limit(8)->get() ?? collect()
            ),
            'categories' => Category::where('published', true)->whereNull('parent_id')->orderBy('position')->get(['id', 'name', 'slug']),
        ]);
    }

    public function index(Request $request)
    {
        $query = Product::published()->with(['variants', 'images', 'categories']);

        // Smart search: read a free-text query into filters (AI when configured).
        // Filters the shopper picked by hand always win; ?exact=1 skips it.
        $ai = null;
        if ($request->filled('q') && ! $request->boolean('exact')) {
            [$cats, $colors, $sizes] = \App\Support\AiSearch::vocabulary();
            $ai = \App\Support\AiSearch::interpret((string) $request->q, $cats, $colors, $sizes);
            foreach (['category', 'color', 'size', 'min_price', 'max_price', 'sort'] as $k) {
                if (! $request->filled($k) && $ai['filters'][$k] !== null) {
                    $request->merge([$k => $ai['filters'][$k]]);
                } else {
                    $ai['filters'][$k] = null;
                }
            }
        }
        $words = $ai ? (string) $ai['filters']['keywords'] : (string) $request->q;
        foreach (array_filter(preg_split('/\s+/', trim($words))) as $w) {
            $like = '%'.addcslashes($w, '%_\\').'%';
            $query->where(fn ($q) => $q->where('name', 'ilike', $like)->orWhere('description', 'ilike', $like));
        }
        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $request->category));
        }
        if ($request->filled('collection')) {
            $query->whereHas('collections', fn ($q) => $q->where('slug', $request->collection));
        }
        if ($request->filled('color')) {
            $query->whereHas('variants', fn ($q) => $q->where('color', $request->color));
        }
        if ($request->filled('size')) {
            $query->whereHas('variants', fn ($q) => $q->where('size', $request->size));
        }
        if ($request->filled('min_price')) {
            $query->whereHas('variants', fn ($q) => $q->where('price_cents', '>=', (int) $request->min_price * 100));
        }
        if ($request->filled('max_price')) {
            $query->whereHas('variants', fn ($q) => $q->where('price_cents', '<=', (int) $request->max_price * 100));
        }

        $query = match ($request->get('sort')) {
            'price_asc' => $query->orderBy(
                \App\Models\ProductVariant::select('price_cents')->whereColumn('product_id', 'products.id')->orderBy('price_cents')->limit(1)
            ),
            'price_desc' => $query->orderByDesc(
                \App\Models\ProductVariant::select('price_cents')->whereColumn('product_id', 'products.id')->orderBy('price_cents')->limit(1)
            ),
            'best_selling' => $query->orderByDesc(
                \DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->join('product_variants', 'product_variants.id', '=', 'order_items.variant_id')
                    ->where('orders.payment_status', 'paid')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->selectRaw('COALESCE(SUM(order_items.qty), 0)')
            ),
            default => $query->latest('published_at'),
        };

        $products = $query->paginate(12)->withQueryString();

        // Log each new search once: skip paging, filter clicks on the same
        // results (?exact=1) and repeats of the same words within 10 minutes.
        $logKey = 'searched.'.sha1(mb_strtolower(trim((string) $request->q)));
        if ($request->filled('q') && ! $request->has('page') && ! $request->boolean('exact')
            && now()->timestamp - (int) $request->session()->get($logKey, 0) > 600) {
            $request->session()->put($logKey, now()->timestamp);
            \App\Models\SearchLog::create([
                'term' => mb_substr((string) $request->q, 0, 200),
                'filters' => $ai ? array_filter($ai['filters'], fn ($v) => $v !== null) : null,
                'results' => $products->total(),
                'ai' => (bool) ($ai['ai'] ?? false),
                'customer_id' => auth('customer')->id(),
            ]);
        }

        return Inertia::render('Storefront/Shop', [
            'products' => $products->through(fn ($p) => $this->card($p)),
            'categories' => Category::where('published', true)->orderBy('position')->get(['id', 'name', 'slug']),
            'smart' => $ai ? ['ai' => $ai['ai'], 'applied' => array_filter($ai['filters'], fn ($v) => $v !== null)] : null,
            'filters' => $request->only(['q', 'exact', 'category', 'collection', 'color', 'size', 'sort', 'min_price', 'max_price']),
            'searchSaved' => ($cid = auth('customer')->id())
                ? \App\Models\SavedSearch::where('customer_id', $cid)
                    ->where('filters_hash', \App\Models\SavedSearch::hashFor(\App\Models\SavedSearch::normalise($request->query())))->exists()
                : false,
            'colorOptions' => \App\Models\ProductVariant::distinct()->pluck('color')->filter()->values(),
            'sizeOptions' => \App\Models\ProductVariant::distinct()->pluck('size')->filter()->values(),
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::published()->where('slug', $slug)
            ->with(['variants' => fn ($q) => $q->where('active', true), 'images', 'categories', 'brand',
                'reviews' => fn ($q) => $q->where('status', 'published')->with('customer:id,name')->latest()])
            ->firstOrFail();

        $variants = $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'sku' => $v->sku,
            'size' => $v->size,
            'color' => $v->color,
            'price_cents' => $v->price_cents,
            'compare_at_cents' => $v->compare_at_cents,
            'available' => $v->availableQty(),
        ]);

        return Inertia::render('Storefront/Product', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'care_instructions' => $product->care_instructions,
                'brand' => $product->brand?->name,
                'sold' => $this->soldFor($product->id),
                'watchers' => \App\Models\WishlistItem::where('product_id', $product->id)->count(),
                'watched' => ($cid = auth('customer')->id())
                    ? \App\Models\WishlistItem::where('product_id', $product->id)->where('customer_id', $cid)->exists()
                    : false,
                'categories' => $product->categories->map->only(['name', 'slug']),
                'images' => $product->images->map->only(['path', 'alt']),
                'reviews' => $product->reviews->map(fn ($r) => [
                    'rating' => $r->rating, 'body' => $r->body, 'customer' => $r->customer?->name ?? 'Customer', 'date' => $r->created_at->format('d M Y'),
                ]),
            ],
            'variants' => $variants,
            'related' => $this->productCards(
                Product::published()->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $product->categories->pluck('id')))
                    ->where('id', '!=', $product->id)->with('variants', 'images')->limit(12)->get()
            ),
        ]);
    }

    /** Header search suggestions: matching products and categories. */
    public function suggest(Request $request)
    {
        $term = trim((string) $request->get('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['products' => [], 'categories' => []]);
        }
        $like = '%'.addcslashes($term, '%_\\').'%';

        $products = Product::published()->where('name', 'ilike', $like)
            ->with('variants', 'images')->orderBy('name')->limit(6)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price_cents' => $p->variants->min('price_cents'),
                'image' => $p->images->first()?->path,
            ]);

        $categories = Category::where('published', true)->where('name', 'ilike', $like)
            ->orderBy('position')->limit(4)->get(['name', 'slug']);

        // Free preview of how the smart search will read the words (rules only, no AI call).
        [$cats, $colors, $sizes] = \App\Support\AiSearch::vocabulary();
        $f = \App\Support\AiSearch::rules($term, $cats, $colors, $sizes);
        $parts = array_values(array_filter([
            $f['category'] ? $cats[$f['category']] : null,
            $f['color'], $f['size'] ? 'Size '.$f['size'] : null,
            $f['min_price'] ? 'from KES '.number_format($f['min_price']) : null,
            $f['max_price'] ? 'up to KES '.number_format($f['max_price']) : null,
        ]));
        $smart = $parts ? ['label' => implode(' · ', $parts), 'keywords' => $f['keywords']] : null;

        return response()->json(['products' => $products, 'categories' => $categories, 'smart' => $smart]);
    }

    public function page(string $slug)
    {
        $page = CmsPage::where('slug', $slug)->where('published', true)->firstOrFail();

        return Inertia::render('Storefront/Page', ['page' => $page->only(['title', 'body'])]);
    }

    private function productCards($products)
    {
        return $products->map(fn ($p) => $this->card($p))->values();
    }

    /** Units sold per product: paid online orders plus completed POS sales. */
    private ?array $soldMap = null;

    private function soldFor(int $productId): int
    {
        if ($this->soldMap === null) {
            $online = \DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('product_variants', 'product_variants.id', '=', 'order_items.variant_id')
                ->where('orders.payment_status', 'paid')
                ->groupBy('product_variants.product_id')
                ->selectRaw('product_variants.product_id as pid, SUM(order_items.qty) as n')
                ->pluck('n', 'pid');
            $pos = \DB::table('pos_sale_items')
                ->join('pos_sales', 'pos_sales.id', '=', 'pos_sale_items.pos_sale_id')
                ->join('product_variants', 'product_variants.id', '=', 'pos_sale_items.variant_id')
                ->where('pos_sales.status', 'completed')
                ->groupBy('product_variants.product_id')
                ->selectRaw('product_variants.product_id as pid, SUM(pos_sale_items.qty) as n')
                ->pluck('n', 'pid');
            $this->soldMap = [];
            foreach ([$online, $pos] as $set) {
                foreach ($set as $pid => $n) {
                    $this->soldMap[$pid] = ($this->soldMap[$pid] ?? 0) + (int) $n;
                }
            }
        }

        return $this->soldMap[$productId] ?? 0;
    }

    private function card(Product $p): array
    {
        $min = $p->variants->min('price_cents');

        return [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'price_cents' => $min,
            'compare_at_cents' => $p->variants->firstWhere('price_cents', $min)?->compare_at_cents,
            'image' => $p->images->first()?->path,
            'colors' => $p->variants->pluck('color')->unique()->values(),
            'in_stock' => $p->variants->sum(fn ($v) => $v->availableQty()) > 0,
            'is_new' => $p->created_at?->gt(now()->subDays(30)) ?? false,
            'sold' => $this->soldFor($p->id),
        ];
    }
}
