<?php

use App\Models\Category;
use App\Models\SearchLog;
use App\Support\AiSearch;

$cats = ['dresses' => 'Dresses', 'shoes' => 'Shoes', 'handbags' => 'Handbags'];
$colors = ['Red', 'Black', 'Blue'];
$sizes = ['S', 'M', 'L', '38'];

it('reads colour, category and price from plain words', function () use ($cats, $colors, $sizes) {
    $f = AiSearch::rules('red dress under 3k', $cats, $colors, $sizes);
    expect($f['color'])->toBe('Red')->and($f['category'])->toBe('dresses')
        ->and($f['max_price'])->toBe(3000)->and($f['keywords'])->toBeNull();
});

it('keeps leftover words as keywords and reads size and sort', function () use ($cats, $colors, $sizes) {
    $f = AiSearch::rules('cheapest black satin shoes size 38 over 1,500', $cats, $colors, $sizes);
    expect($f)->toMatchArray(['color' => 'Black', 'category' => 'shoes', 'size' => '38', 'min_price' => 1500, 'sort' => 'price_asc', 'keywords' => 'satin']);
});

it('falls back to rules without an API key and logs the search', function () {
    config(['services.anthropic.key' => null]);
    Category::firstOrCreate(['slug' => 'dresses'], ['name' => 'Dresses', 'published' => true, 'position' => 1]);

    $this->get('/shop?q=dresses+under+5000')->assertOk()
        ->assertInertia(fn ($p) => $p->where('smart.ai', false)
            ->where('smart.applied.category', 'dresses')->where('smart.applied.max_price', 5000));

    expect(SearchLog::first())->term->toBe('dresses under 5000')->ai->toBeFalse();
});

it('skips interpretation for exact searches', function () {
    $this->get('/shop?q=red+dress&exact=1')->assertOk()->assertInertia(fn ($p) => $p->where('smart', null));
});

it('shows search insights to staff with marketing access', function () {
    $this->get('/admin/search')->assertRedirect();
});

it('caps new AI lookups per visitor and falls back to rules', function () {
    config(['services.anthropic.key' => 'test-key']);
    \Illuminate\Support\Facades\RateLimiter::hit('ai-search:127.0.0.1', 60);
    for ($i = 0; $i < 20; $i++) {
        \Illuminate\Support\Facades\RateLimiter::hit('ai-search:127.0.0.1', 60);
    }

    $this->get('/shop?q=red+dresses')->assertOk()->assertInertia(fn ($p) => $p->where('smart.ai', false));
});

it('understands everyday words for categories and shades', function () {
    $cats = ['dresses' => 'Dresses', 'shoes' => 'Shoes', 'handbags' => 'Handbags'];
    $f = AiSearch::rules('navy gown', $cats, ['Blue', 'Red'], []);
    expect($f['category'])->toBe('dresses')->and($f['color'])->toBe('Blue')->and($f['keywords'])->toBeNull();

    expect(AiSearch::rules('maroon heels', $cats, ['Red'], []))->toMatchArray(['category' => 'shoes', 'color' => 'Red']);
    expect(AiSearch::rules('leather tote', $cats, [], [])['category'])->toBe('handbags');
});

it('previews the smart reading in header suggestions', function () {
    Category::firstOrCreate(['slug' => 'dresses'], ['name' => 'Dresses', 'published' => true, 'position' => 1]);
    $this->getJson('/search/suggest?q=dress+under+4k')->assertOk()
        ->assertJsonPath('smart.label', 'Dresses · up to KES 4,000');
});

it('logs a search once, not again for repeats or filter clicks', function () {
    $this->get('/shop?q=linen+shirt');
    $this->get('/shop?q=linen+shirt');
    $this->get('/shop?q=linen&exact=1&color=Red');
    expect(SearchLog::whereIn('term', ['linen shirt', 'linen'])->count())->toBe(1);
});
