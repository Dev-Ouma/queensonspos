<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('renders the marketplace storefront pages', function () {
    $slug = Product::published()->first()->slug;

    foreach (['/', '/shop', '/shop?q=dress', "/product/{$slug}", '/cart', '/account/login', '/account/register'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('shares sold counts on product cards and the product page', function () {
    $slug = Product::published()->first()->slug;

    $this->get('/shop')->assertInertia(fn ($page) => $page->has('products.data.0.sold'));
    $this->get("/product/{$slug}")->assertInertia(fn ($page) => $page
        ->has('product.sold')->has('product.watchers')->where('product.watched', false));
});

it('shows customer account pages while a staff member is also signed in', function () {
    $staff = User::first();
    $customer = Customer::first();

    $this->actingAs($staff, 'web')->actingAs($customer, 'customer');

    foreach (['/account', '/account/orders', '/account/wishlist'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('suggests matching products and categories for the header search', function () {
    $product = Product::published()->first();
    $term = substr($product->name, 0, 4);

    $this->getJson('/search/suggest?q='.urlencode($term))
        ->assertOk()
        ->assertJsonPath('products.0.slug', fn ($slug) => is_string($slug))
        ->assertJsonStructure(['products' => [['id', 'name', 'slug', 'price_cents', 'image']], 'categories']);

    $this->getJson('/search/suggest?q=a')->assertOk()->assertExactJson(['products' => [], 'categories' => []]);
    $this->getJson('/search/suggest?q=%25')->assertOk();
});

it('sorts results by best selling', function () {
    $this->get('/shop?sort=best_selling')->assertOk()->assertInertia(fn ($page) => $page->has('products.data'));
});

it('lets a customer save, list and delete a search', function () {
    $customer = Customer::first();
    $this->actingAs($customer, 'customer');

    $this->post('/account/saved-searches/toggle', ['q' => 'dress', 'category' => 'dresses', 'size' => 'M'])->assertRedirect();
    $saved = \App\Models\SavedSearch::where('customer_id', $customer->id)->sole();
    expect($saved->label)->toBe('"dress" in Dresses · Size M')
        ->and($saved->url())->toContain('q=dress');

    $this->get('/shop?size=M&q=dress&category=dresses')->assertInertia(fn ($page) => $page->where('searchSaved', true));
    $this->get('/account/saved-searches')->assertOk()->assertInertia(fn ($page) => $page->has('searches', 1));

    // Saving the same filters again removes it (toggle)
    $this->post('/account/saved-searches/toggle', ['category' => 'dresses', 'size' => 'M', 'q' => 'dress']);
    expect(\App\Models\SavedSearch::count())->toBe(0);
});

it('does not let a customer delete another customer\'s saved search', function () {
    [$a, $b] = Customer::take(2)->get();
    $search = \App\Models\SavedSearch::create(['customer_id' => $a->id, 'label' => 'x', 'filters' => ['q' => 'x'], 'filters_hash' => 'h']);

    $this->actingAs($b, 'customer')->delete("/account/saved-searches/{$search->id}")->assertNotFound();
    expect($search->fresh())->not->toBeNull();
});

it('sends guests to sign in before saving a search', function () {
    $this->post('/account/saved-searches/toggle', ['q' => 'dress'])->assertRedirect('/account/login');
});
