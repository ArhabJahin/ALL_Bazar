<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'home'])->name('home');
Route::get('/search', [MarketplaceController::class, 'search'])->name('search');
Route::get('/search-suggestions', [MarketplaceController::class, 'suggestions'])->name('search.suggestions');
Route::get('/advanced-search', [MarketplaceController::class, 'advancedSearch'])->name('advanced-search');
Route::get('/compare', [MarketplaceController::class, 'compare'])->name('compare');
Route::get('/notifications', [MarketplaceController::class, 'notifications'])->name('notifications');
Route::get('/rider-dashboard', [MarketplaceController::class, 'riderDashboard'])->name('rider.dashboard');
Route::get('/shop-verification', [MarketplaceController::class, 'shopVerification'])->name('shop.verification');
Route::get('/admin-activity-log', [MarketplaceController::class, 'activityLog'])->name('admin.activity-log');
Route::get('/coupons', [MarketplaceController::class, 'coupons'])->name('coupons');
Route::get('/returns', [MarketplaceController::class, 'returns'])->name('returns');
Route::get('/payment/success', [MarketplaceController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/failed', [MarketplaceController::class, 'paymentFailed'])->name('payment.failed');
Route::get('/orders/demo', [MarketplaceController::class, 'orderDetails'])->name('orders.demo');
Route::get('/orders/demo/invoice', [MarketplaceController::class, 'invoice'])->name('orders.invoice');
Route::get('/products', [ProductController::class, 'index'])->name('products.browse');
Route::get('/products/{slug}', [MarketplaceController::class, 'product'])->name('products.show');
Route::get('/shops', [MarketplaceController::class, 'map'])->name('shops.map');
Route::get('/shops/{slug}', [MarketplaceController::class, 'shop'])->name('shops.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
Route::post('/checkout', [CartController::class, 'placeOrder'])->name('cart.place-order');
Route::get('/account', [MarketplaceController::class, 'account'])->middleware('auth')->name('account.dashboard');
Route::get('/shop-owner', [MarketplaceController::class, 'owner'])->middleware('role:shop_owner,admin,co_admin')->name('owner.dashboard');
Route::get('/admin', [MarketplaceController::class, 'admin'])->middleware('role:admin,co_admin')->name('admin.dashboard');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/pages/{page}', [MarketplaceController::class, 'page'])->name('pages.static');
Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->middleware('auth')->name('wishlist.toggle');
Route::post('/reviews/products/{product}', [ReviewController::class, 'product'])->middleware('auth')->name('reviews.products.store');
Route::post('/reviews/shops/{shop}', [ReviewController::class, 'shop'])->middleware('auth')->name('reviews.shops.store');
Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->middleware('auth')->name('account.profile.update');
Route::post('/account/addresses', [AccountController::class, 'storeAddress'])->middleware('auth')->name('account.addresses.store');
Route::delete('/account/addresses/{address}', [AccountController::class, 'deleteAddress'])->middleware('auth')->name('account.addresses.destroy');
Route::post('/support', [SupportController::class, 'store'])->name('support.store');

Route::prefix('manage')->middleware('role:shop_owner,admin,co_admin')->group(function () {
    Route::resource('products', ProductController::class)->except(['create', 'edit', 'show']);
    Route::delete('product-images/{image}', [ProductImageController::class, 'destroy'])->name('product-images.destroy');
    Route::resource('shops', ShopController::class)->except(['create', 'edit', 'show']);
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
});

Route::prefix('admin/actions')->middleware('role:admin')->group(function () {
    Route::resource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
    Route::post('users/{user}/co-admin', [AdminController::class, 'promoteToCoAdmin'])->name('admin.co-admin.promote');
    Route::delete('users/{user}/co-admin', [AdminController::class, 'removeCoAdmin'])->name('admin.co-admin.remove');
    Route::patch('users/{user}/role', [AdminController::class, 'updateUserRole'])->name('admin.users.role');
    Route::patch('shops/{shop}/status', [AdminController::class, 'updateShopStatus'])->name('admin.shops.status');
    Route::patch('support/{supportMessage}/status', [SupportController::class, 'updateStatus'])->name('admin.support.status');
});
