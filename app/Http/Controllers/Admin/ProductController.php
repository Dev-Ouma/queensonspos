<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['variants.balances', 'categories', 'images']);
        if ($request->filled('q')) {
            $query->where(fn ($w) => $w->where('name', 'ilike', "%{$request->q}%")->orWhere('sku_root', 'ilike', "%{$request->q}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $request->category));
        }

        return Inertia::render('Admin/Products/Index', [
            'products' => $query->latest()->paginate(15)->withQueryString()->through(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku_root' => $p->sku_root,
                'status' => $p->status,
                'featured' => $p->featured,
                'categories' => $p->categories->pluck('name'),
                'variant_count' => $p->variants->count(),
                'stock' => $p->variants->sum(fn ($v) => $v->balances->sum(fn ($b) => $b->on_hand - $b->reserved - $b->damaged)),
                'min_price_cents' => $p->variants->min('price_cents'),
            ]),
            'categories' => Category::orderBy('position')->get(['id', 'name']),
            'filters' => $request->only(['q', 'status', 'category']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => null,
            'brands' => Brand::get(['id', 'name']),
            'categories' => Category::orderBy('position')->get(['id', 'name']),
            'collections' => Collection::get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $product = Product::create($data + [
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);
        $product->categories()->sync($request->input('category_ids', []));
        $product->collections()->sync($request->input('collection_ids', []));
        $this->syncVariants($product, $request->input('variants', []));

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $product->load(['variants.balances', 'categories', 'collections', 'images']);

        return Inertia::render('Admin/Products/Form', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku_root' => $product->sku_root,
                'brand_id' => $product->brand_id,
                'description' => $product->description,
                'care_instructions' => $product->care_instructions,
                'status' => $product->status,
                'featured' => $product->featured,
                'low_stock_threshold' => $product->low_stock_threshold,
                'seo_title' => $product->seo_title,
                'seo_description' => $product->seo_description,
                'category_ids' => $product->categories->pluck('id'),
                'collection_ids' => $product->collections->pluck('id'),
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id, 'sku' => $v->sku, 'barcode' => $v->barcode, 'size' => $v->size, 'color' => $v->color,
                    'price' => $v->price_cents / 100, 'cost' => $v->cost_cents / 100, 'active' => $v->active,
                    'stock' => $v->balances->sum(fn ($b) => $b->on_hand - $b->reserved - $b->damaged),
                ]),
            ],
            'brands' => Brand::get(['id', 'name']),
            'categories' => Category::orderBy('position')->get(['id', 'name']),
            'collections' => Collection::get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product->id);
        if ($data['status'] === 'published' && ! $product->published_at) {
            $data['published_at'] = now();
        }
        $product->update($data);
        $product->categories()->sync($request->input('category_ids', []));
        $product->collections()->sync($request->input('collection_ids', []));
        $this->syncVariants($product, $request->input('variants', []));

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->update(['status' => 'archived']);

        return back()->with('success', 'Product archived.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:200',
            'sku_root' => 'required|string|max:50|unique:products,sku_root'.($id ? ",$id" : ''),
            'brand_id' => 'nullable|exists:brands,id',
            'description' => 'nullable|string',
            'care_instructions' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'featured' => 'boolean',
            'low_stock_threshold' => 'integer|min:0',
            'seo_title' => 'nullable|string|max:150',
            'seo_description' => 'nullable|string|max:300',
        ]);
    }

    private function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $v) {
            $payload = [
                'sku' => $v['sku'],
                'barcode' => $v['barcode'] ?? null,
                'size' => $v['size'] ?? null,
                'color' => $v['color'] ?? null,
                'price_cents' => (int) round(($v['price'] ?? 0) * 100),
                'cost_cents' => (int) round(($v['cost'] ?? 0) * 100),
                'active' => $v['active'] ?? true,
            ];
            if (! empty($v['id'])) {
                ProductVariant::where('product_id', $product->id)->whereKey($v['id'])->first()?->update($payload);
            } else {
                $product->variants()->create($payload);
            }
        }
    }
}
