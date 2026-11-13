<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Storefront/Cart', $this->cartProps($request));
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'qty' => 'required|integer|min:1|max:50',
        ]);

        $variant = ProductVariant::findOrFail($data['variant_id']);
        if ($variant->availableQty() < $data['qty']) {
            return back()->withErrors(['cart' => 'Not enough stock available.']);
        }

        $cart = $request->session()->get('cart', []);
        $key = (string) $variant->id;
        $cart[$key] = ($cart[$key] ?? 0) + $data['qty'];
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Added to cart.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|integer',
            'qty' => 'required|integer|min:0|max:50',
        ]);
        $cart = $request->session()->get('cart', []);
        if ($data['qty'] === 0) {
            unset($cart[(string) $data['variant_id']]);
        } else {
            $cart[(string) $data['variant_id']] = $data['qty'];
        }
        $request->session()->put('cart', $cart);

        return back();
    }

    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => 'required|string|max:40']);
        $coupon = Coupon::where('code', strtoupper($request->code))->first();
        $subtotal = $this->subtotal($request);
        if (! $coupon || ! $coupon->isValid($subtotal)) {
            return back()->withErrors(['coupon' => 'Invalid or expired promo code.']);
        }
        $request->session()->put('coupon', $coupon->code);

        return back()->with('success', 'Promo code applied.');
    }

    public static function lines(Request $request): array
    {
        $cart = $request->session()->get('cart', []);
        if (! $cart) {
            return [];
        }
        $variants = ProductVariant::with('product.images')->whereIn('id', array_keys($cart))->get();

        return $variants->map(fn ($v) => [
            'variant_id' => $v->id,
            'name' => $v->product->name,
            'slug' => $v->product->slug,
            'sku' => $v->sku,
            'size' => $v->size,
            'color' => $v->color,
            'image' => $v->product->images->first()?->path,
            'price_cents' => $v->price_cents,
            'qty' => (int) $cart[(string) $v->id],
            'available' => $v->availableQty(),
            'line_total_cents' => $v->price_cents * (int) $cart[(string) $v->id],
        ])->values()->all();
    }

    private function subtotal(Request $request): int
    {
        return array_sum(array_column(self::lines($request), 'line_total_cents'));
    }

    public function cartProps(Request $request): array
    {
        $lines = self::lines($request);
        $subtotal = array_sum(array_column($lines, 'line_total_cents'));
        $coupon = null;
        $discount = 0;
        if ($code = $request->session()->get('coupon')) {
            $coupon = Coupon::where('code', $code)->first();
            if ($coupon && $coupon->isValid($subtotal)) {
                $discount = $coupon->discountFor($subtotal);
            } else {
                $coupon = null;
                $request->session()->forget('coupon');
            }
        }
        $delivery = $subtotal > 0 ? (int) (Setting::where('key', 'delivery_fee_cents')->value('value') ?? 30000) : 0;
        if ($coupon?->type === 'free_delivery') {
            $delivery = 0;
        }

        return [
            'lines' => $lines,
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'delivery_cents' => $delivery,
            'total_cents' => $subtotal - $discount + $delivery,
            'coupon' => $coupon?->code,
        ];
    }

    public static function token(Request $request): string
    {
        if (! $request->session()->has('cart_token')) {
            $request->session()->put('cart_token', (string) Str::uuid());
        }

        return $request->session()->get('cart_token');
    }
}
