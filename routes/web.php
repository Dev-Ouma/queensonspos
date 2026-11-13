<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Admin\CatalogAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OperationsController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront (public)
|--------------------------------------------------------------------------
*/
Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/search/suggest', [ShopController::class, 'suggest'])->middleware('throttle:60,1')->name('search.suggest');
Route::get('/product/{slug}', [ShopController::class, 'show'])->name('product.show');
Route::get('/page/{slug}', [ShopController::class, 'page'])->name('cms.page');

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');
Route::get('/checkout/confirmation/{reference}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

/*
|--------------------------------------------------------------------------
| Customer portal /account
|--------------------------------------------------------------------------
*/
Route::prefix('account')->name('account.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/login', [AccountController::class, 'loginForm'])->name('login');
        Route::post('/login', [AccountController::class, 'login'])->name('login.post');
        Route::get('/register', [AccountController::class, 'registerForm'])->name('register');
        Route::post('/register', [AccountController::class, 'register'])->name('register.post');
    });

    Route::middleware('auth:customer')->group(function () {
        Route::post('/logout', [AccountController::class, 'logout'])->name('logout');
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
        Route::get('/orders/{reference}', [AccountController::class, 'orderShow'])->name('orders.show');
        Route::get('/wishlist', [AccountController::class, 'wishlist'])->name('wishlist');
        Route::post('/wishlist/toggle', [AccountController::class, 'wishlistToggle'])->name('wishlist.toggle');
        Route::get('/saved-searches', [\App\Http\Controllers\Storefront\SavedSearchController::class, 'index'])->name('saved-searches');
        Route::post('/saved-searches/toggle', [\App\Http\Controllers\Storefront\SavedSearchController::class, 'toggle'])->name('saved-searches.toggle');
        Route::delete('/saved-searches/{savedSearch}', [\App\Http\Controllers\Storefront\SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');
        Route::post('/reviews', [AccountController::class, 'reviewStore'])->name('reviews.store');
        Route::patch('/profile', [AccountController::class, 'profileUpdate'])->name('profile.update');
    });
});

/*
|--------------------------------------------------------------------------
| Administration /admin (staff guard)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');

    Route::middleware('permission:products.view')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->middleware('permission:products.create')->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->middleware('permission:products.create')->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('permission:products.edit')->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete')->name('products.destroy');
        Route::get('/reviews', [CatalogAdminController::class, 'reviews'])->name('reviews');
        Route::patch('/reviews/{review}', [CatalogAdminController::class, 'reviewModerate'])->middleware('permission:products.edit')->name('reviews.moderate');
    });

    Route::middleware('permission:categories.view')->group(function () {
        Route::get('/categories', [CatalogAdminController::class, 'categories'])->name('categories');
        Route::post('/categories', [CatalogAdminController::class, 'categoryStore'])->middleware('permission:categories.create')->name('categories.store');
        Route::patch('/categories/{category}', [CatalogAdminController::class, 'categoryUpdate'])->middleware('permission:categories.edit')->name('categories.update');
        Route::delete('/categories/{category}', [CatalogAdminController::class, 'categoryDestroy'])->middleware('permission:categories.delete')->name('categories.destroy');
    });

    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/ledger', [InventoryController::class, 'ledger'])->name('inventory.ledger');
        Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->middleware('permission:inventory.adjust')->name('inventory.adjust');
        Route::get('/inventory/variant-search', [InventoryController::class, 'variantSearch'])->name('inventory.variant-search');
    });

    Route::middleware('permission:orders.view')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/transition', [OrderController::class, 'transition'])->middleware('permission:orders.edit')->name('orders.transition');
    });

    Route::get('/customers', [OperationsController::class, 'customers'])->middleware('permission:customers.view')->name('customers');

    Route::middleware('permission:customers.view')->group(function () {
        Route::get('/tickets', [\App\Http\Controllers\Admin\CrmController::class, 'tickets'])->name('tickets');
        Route::post('/tickets/{ticket}/reply', [\App\Http\Controllers\Admin\CrmController::class, 'ticketReply'])->middleware('permission:customers.edit')->name('tickets.reply');
    });

    Route::get('/transfers', [\App\Http\Controllers\Admin\CrmController::class, 'transfers'])->middleware('permission:inventory.view')->name('transfers');

    Route::get('/deliveries', [\App\Http\Controllers\Admin\CrmController::class, 'deliveries'])->middleware('permission:orders.view')->name('deliveries');
    Route::patch('/deliveries/{delivery}', [\App\Http\Controllers\Admin\CrmController::class, 'deliveryUpdate'])->middleware('permission:orders.edit')->name('deliveries.update');

    Route::middleware('permission:suppliers.view')->group(function () {
        Route::get('/suppliers', [OperationsController::class, 'suppliers'])->name('suppliers');
        Route::post('/suppliers', [OperationsController::class, 'supplierSave'])->middleware('permission:suppliers.create')->name('suppliers.save');
    });

    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('/purchases', [OperationsController::class, 'purchaseOrders'])->name('purchases.index');
        Route::post('/purchases', [OperationsController::class, 'purchaseOrderStore'])->middleware('permission:purchases.create')->name('purchases.store');
        Route::get('/purchases/{purchaseOrder}', [OperationsController::class, 'purchaseOrderShow'])->name('purchases.show');
        Route::post('/purchases/{purchaseOrder}/receive', [OperationsController::class, 'receiveGoods'])->middleware('permission:purchases.edit')->name('purchases.receive');
    });

    Route::middleware('permission:finance.view')->group(function () {
        Route::get('/expenses', [OperationsController::class, 'expenses'])->name('expenses');
        Route::post('/expenses', [OperationsController::class, 'expenseStore'])->middleware('permission:finance.create')->name('expenses.store');
        Route::patch('/expenses/{expense}', [OperationsController::class, 'expenseApprove'])->middleware('permission:finance.approve')->name('expenses.approve');
    });

    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/export', [ReportController::class, 'exportCsv'])->middleware('permission:reports.export')->name('reports.export');
    });

    Route::middleware('permission:marketing.view')->group(function () {
        Route::get('/coupons', [CatalogAdminController::class, 'coupons'])->name('coupons');
        Route::post('/coupons', [CatalogAdminController::class, 'couponStore'])->middleware('permission:marketing.create')->name('coupons.store');
        Route::patch('/coupons/{coupon}/toggle', [CatalogAdminController::class, 'couponToggle'])->middleware('permission:marketing.edit')->name('coupons.toggle');
    });

    Route::middleware('permission:cms.view')->group(function () {
        Route::get('/cms', [CatalogAdminController::class, 'cmsPages'])->name('cms');
        Route::post('/cms/pages', [CatalogAdminController::class, 'cmsPageSave'])->middleware('permission:cms.edit')->name('cms.pages.save');
        Route::post('/cms/banners', [CatalogAdminController::class, 'bannerSave'])->middleware('permission:cms.edit')->name('cms.banners.save');
        Route::post('/cms/storefront', [CatalogAdminController::class, 'storefrontSave'])->middleware('permission:cms.edit')->name('cms.storefront.save');
    });

    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [SystemController::class, 'users'])->name('users');
        Route::post('/users', [SystemController::class, 'userSave'])->middleware('permission:users.create')->name('users.save');
    });

    Route::middleware('permission:settings.view')->group(function () {
        Route::get('/settings', [SystemController::class, 'settings'])->name('settings');
        Route::post('/settings', [SystemController::class, 'settingsSave'])->middleware('permission:settings.edit')->name('settings.save');
    });

    Route::get('/audit', [SystemController::class, 'audit'])->middleware('permission:audit.view')->name('audit');

    Route::get('/guide', [SystemController::class, 'guide'])->name('guide');
});

/*
|--------------------------------------------------------------------------
| POS (staff guard, pos permission)
|--------------------------------------------------------------------------
*/
Route::prefix('pos')->name('pos.')->middleware(['auth', 'verified', 'permission:pos.view'])->group(function () {
    Route::get('/', [PosController::class, 'terminal'])->name('terminal');
    Route::post('/session/open', [PosController::class, 'openSession'])->name('session.open');
    Route::post('/session/close', [PosController::class, 'closeSession'])->name('session.close');
    Route::get('/search', [PosController::class, 'search'])->name('search');
    Route::post('/sale', [PosController::class, 'sale'])->middleware('permission:pos.create')->name('sale');
    Route::get('/receipt/{reference}', [PosController::class, 'receipt'])->name('receipt');
    Route::get('/sale-lookup', [PosController::class, 'saleLookup'])->name('sale.lookup');
    Route::post('/return', [PosController::class, 'processReturn'])->middleware('permission:pos.create')->name('return');
    Route::post('/cash-movement', [PosController::class, 'cashMovement'])->name('cash.movement');
});

/*
|--------------------------------------------------------------------------
| Staff profile (Breeze)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
