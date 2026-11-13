<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Always the staff guard: customer routes switch the default guard to "customer".
        $user = $request->user('web');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name,
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
                'customer' => $request->user('customer')?->only(['id', 'name', 'email']),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'lastSale' => $request->session()->get('lastSale'),
            ],
            'cartCount' => array_sum($request->session()->get('cart', [])),
            'brand' => [
                'name' => 'QUEENSONS',
                'tagline' => 'Style Beyond Seasons.',
            ],
            'storefront' => fn () => \App\Support\StorefrontContent::cached(),
            'socialProof' => fn () => cache()->remember('storefront.social_proof', 300, function () {
                return \App\Models\OrderItem::with('order:id,placed_at')
                    ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
                    ->latest('id')->limit(8)->get()
                    ->map(fn ($i) => ['name' => $i->name_snapshot])
                    ->unique('name')->values()->take(6)->all();
            }),
        ];
    }
}
