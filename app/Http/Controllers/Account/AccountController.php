<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductReview;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function loginForm()
    {
        return Inertia::render('Account/Login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required']);
        if (! Auth::guard('customer')->attempt($data, true)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'));
    }

    public function registerForm()
    {
        return Inertia::render('Account/Register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:120|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $customer = Customer::create($data + ['email_verified_at' => now()]);
        Auth::guard('customer')->login($customer, true);

        return redirect()->route('account.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function dashboard()
    {
        $customer = Auth::guard('customer')->user();

        return Inertia::render('Account/Dashboard', [
            'customer' => $customer->only(['name', 'email', 'phone', 'loyalty_points']),
            'recentOrders' => $customer->orders()->latest('placed_at')->limit(5)->get()
                ->map(fn ($o) => $o->only(['reference', 'status', 'total_cents']) + ['placed_at' => $o->placed_at?->format('d M Y')]),
            'wishlistCount' => $customer->wishlistItems()->count(),
        ]);
    }

    public function orders()
    {
        $customer = Auth::guard('customer')->user();

        return Inertia::render('Account/Orders', [
            'orders' => $customer->orders()->with('items')->latest('placed_at')->paginate(10)
                ->through(fn ($o) => [
                    'reference' => $o->reference,
                    'status' => $o->status,
                    'payment_status' => $o->payment_status,
                    'total_cents' => $o->total_cents,
                    'placed_at' => $o->placed_at?->format('d M Y H:i'),
                    'items' => $o->items->map->only(['name_snapshot', 'qty', 'unit_price_cents']),
                ]),
        ]);
    }

    public function orderShow(string $reference)
    {
        $order = Order::where('reference', $reference)
            ->where('customer_id', Auth::guard('customer')->id())
            ->with(['items', 'statusHistory'])
            ->firstOrFail();

        return Inertia::render('Account/OrderDetail', [
            'order' => [
                'reference' => $order->reference,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'subtotal_cents' => $order->subtotal_cents,
                'discount_cents' => $order->discount_cents,
                'delivery_cents' => $order->delivery_cents,
                'total_cents' => $order->total_cents,
                'placed_at' => $order->placed_at?->format('d M Y H:i'),
                'items' => $order->items->map->only(['name_snapshot', 'sku_snapshot', 'qty', 'unit_price_cents', 'line_total_cents']),
                'history' => $order->statusHistory->map(fn ($h) => [
                    'to' => $h->to_status, 'at' => $h->created_at->format('d M Y H:i'), 'note' => $h->note,
                ]),
            ],
        ]);
    }

    public function wishlist()
    {
        $customer = Auth::guard('customer')->user();

        return Inertia::render('Account/Wishlist', [
            'items' => $customer->wishlistItems()->with('product.images', 'product.variants')->get()->map(fn ($w) => [
                'id' => $w->product_id,
                'name' => $w->product->name,
                'slug' => $w->product->slug,
                'image' => $w->product->images->first()?->path,
                'price_cents' => $w->product->variants->min('price_cents'),
            ]),
        ]);
    }

    public function wishlistToggle(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $customerId = Auth::guard('customer')->id();
        $existing = WishlistItem::where('customer_id', $customerId)->where('product_id', $request->product_id)->first();
        $existing ? $existing->delete() : WishlistItem::create(['customer_id' => $customerId, 'product_id' => $request->product_id]);

        return back();
    }

    public function reviewStore(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'nullable|string|max:2000',
        ]);
        ProductReview::create($data + ['customer_id' => Auth::guard('customer')->id(), 'status' => 'pending']);

        return back()->with('success', 'Review submitted for moderation.');
    }

    public function profileUpdate(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        $customer->name = $data['name'];
        $customer->phone = $data['phone'];
        if (! empty($data['password'])) {
            $customer->password = Hash::make($data['password']);
        }
        $customer->save();

        return back()->with('success', 'Profile updated.');
    }
}
