<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Coupon;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CatalogAdminController extends Controller
{
    public function categories()
    {
        return Inertia::render('Admin/Categories', [
            'categories' => Category::withCount('products')->orderBy('position')->get(),
        ]);
    }

    public function categoryStore(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'parent_id' => 'nullable|exists:categories,id']);
        Category::create($data + ['slug' => Str::slug($data['name']), 'position' => Category::max('position') + 1]);

        return back()->with('success', 'Category created.');
    }

    public function categoryUpdate(Request $request, Category $category)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'published' => 'boolean', 'position' => 'integer']);
        $category->update($data);

        return back()->with('success', 'Category updated.');
    }

    public function categoryDestroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Category has products; reassign them first.']);
        }
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    public function coupons()
    {
        return Inertia::render('Admin/Coupons', [
            'coupons' => Coupon::latest()->get()->map(fn ($c) => $c->toArray() + [
                'starts_at' => $c->starts_at?->format('Y-m-d'),
                'ends_at' => $c->ends_at?->format('Y-m-d'),
            ]),
        ]);
    }

    public function couponStore(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:40|unique:coupons,code',
            'type' => 'required|in:percent,fixed,free_delivery',
            'value' => 'required|integer|min:0',
            'min_order_cents' => 'integer|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
        $data['code'] = strtoupper($data['code']);
        if ($data['type'] === 'fixed') {
            $data['value'] *= 100; // entered in KES
        }
        Coupon::create($data + ['active' => true]);

        return back()->with('success', 'Coupon created.');
    }

    public function couponToggle(Coupon $coupon)
    {
        $coupon->update(['active' => ! $coupon->active]);

        return back();
    }

    public function cmsPages()
    {
        return Inertia::render('Admin/Cms', [
            'pages' => CmsPage::orderBy('title')->get(),
            'banners' => Banner::orderBy('position')->get(),
            'storefrontContent' => \App\Support\StorefrontContent::all(),
        ]);
    }

    public function storefrontSave(Request $request)
    {
        $data = $request->validate([
            'hero_slides' => 'required|array|min:1|max:5',
            'hero_slides.*.img' => 'required|url|max:500',
            'hero_slides.*.eyebrow' => 'required|string|max:80',
            'hero_slides.*.line1' => 'required|string|max:60',
            'hero_slides.*.line2' => 'required|string|max:60',
            'announcements' => 'required|array|min:1|max:8',
            'announcements.*' => 'required|string|max:120',
            'marquee' => 'required|array|min:1|max:10',
            'marquee.*' => 'required|string|max:80',
            'flash_sale' => 'required|array',
            'flash_sale.enabled' => 'required|boolean',
            'flash_sale.eyebrow' => 'required|string|max:40',
            'flash_sale.title' => 'required|string|max:80',
            'flash_sale.highlight' => 'required|string|max:80',
            'flash_sale.subtitle' => 'required|string|max:160',
            'flash_sale.cta' => 'required|string|max:40',
            'flash_sale.link' => 'required|string|max:200',
            'testimonials' => 'required|array|min:1|max:8',
            'testimonials.*.quote' => 'required|string|max:300',
            'testimonials.*.name' => 'required|string|max:60',
            'testimonials.*.city' => 'required|string|max:60',
            'stats' => 'required|array|size:4',
            'stats.*.label' => 'required|string|max:40',
            'stats.*.target' => 'required|integer|min:0',
            'stats.*.suffix' => 'nullable|string|max:3',
            'socials' => 'required|array',
            'socials.instagram' => 'nullable|url|max:250',
            'socials.tiktok' => 'nullable|url|max:250',
            'socials.whatsapp' => 'nullable|url|max:250',
            'social_proof_enabled' => 'required|boolean',
            'free_delivery_threshold_cents' => 'required|integer|min:0',
        ]);
        $data['stats'] = array_map(fn ($s) => $s + ['suffix' => ''], $data['stats']);

        \App\Support\StorefrontContent::save($data);

        return back()->with('success', 'Storefront content published.');
    }

    public function cmsPageSave(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|exists:cms_pages,id',
            'title' => 'required|string|max:150',
            'body' => 'nullable|string',
            'published' => 'boolean',
        ]);
        if (! empty($data['id'])) {
            CmsPage::find($data['id'])->update($data);
        } else {
            CmsPage::create($data + ['slug' => Str::slug($data['title'])]);
        }

        return back()->with('success', 'Page saved.');
    }

    public function bannerSave(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|exists:banners,id',
            'title' => 'required|string|max:150',
            'subtitle' => 'nullable|string|max:250',
            'link' => 'nullable|string|max:250',
            'position' => 'integer',
            'active' => 'boolean',
        ]);
        ! empty($data['id']) ? Banner::find($data['id'])->update($data) : Banner::create($data);

        return back()->with('success', 'Banner saved.');
    }

    public function reviews()
    {
        return Inertia::render('Admin/Reviews', [
            'reviews' => ProductReview::with(['product:id,name', 'customer:id,name'])->latest()->paginate(20)
                ->through(fn ($r) => [
                    'id' => $r->id, 'product' => $r->product->name, 'customer' => $r->customer->name,
                    'rating' => $r->rating, 'body' => $r->body, 'status' => $r->status, 'at' => $r->created_at->format('d M Y'),
                ]),
        ]);
    }

    public function reviewModerate(Request $request, ProductReview $review)
    {
        $request->validate(['status' => 'required|in:published,rejected']);
        $review->update(['status' => $request->status]);

        return back();
    }
}
