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

        if ($request->filled('q')) {
            $query->where('name', 'ilike', '%'.$request->q.'%');
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
            default => $query->latest('published_at'),
        };

        $products = $query->paginate(12)->withQueryString();

        return Inertia::render('Storefront/Shop', [
            'products' => $products->through(fn ($p) => $this->card($p)),
            'categories' => Category::where('published', true)->orderBy('position')->get(['id', 'name', 'slug']),
            'filters' => $request->only(['q', 'category', 'collection', 'color', 'size', 'sort', 'min_price', 'max_price']),
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
                'categories' => $product->categories->map->only(['name', 'slug']),
                'images' => $product->images->map->only(['path', 'alt']),
                'reviews' => $product->reviews->map(fn ($r) => [
                    'rating' => $r->rating, 'body' => $r->body, 'customer' => $r->customer?->name ?? 'Customer', 'date' => $r->created_at->format('d M Y'),
                ]),
            ],
            'variants' => $variants,
            'related' => $this->productCards(
                Product::published()->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $product->categories->pluck('id')))
                    ->where('id', '!=', $product->id)->with('variants', 'images')->limit(4)->get()
            ),
        ]);
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
