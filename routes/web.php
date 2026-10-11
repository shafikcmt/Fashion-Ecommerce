<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogCsvController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StoreController;
use App\Livewire\ShoppingBag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreController::class, 'home'])->name('home');
Route::get('/shop', [StoreController::class, 'catalog'])->name('catalog');
Route::get('/category/{category}', [StoreController::class, 'catalog'])->name('category');
Route::get('/collection/{collection}', fn (Request $r, string $collection) => app(StoreController::class)->catalog($r, null, $collection))->name('collection');
Route::get('/products/{slug}', [StoreController::class, 'product'])->name('product');
Route::get('/pages/{slug}', [StoreController::class, 'page'])->name('page');
Route::post('/locale', function (Request $r) {
    $data = $r->validate(['locale' => 'required|in:en,bn']);
    session($data);

    return back();
})->name('locale');
Route::get('/bag', ShoppingBag::class)->name('bag');
Route::get('/checkout', [CheckoutController::class, 'form'])->name('checkout');
Route::post('/checkout/review', [CheckoutController::class, 'review'])->middleware('throttle:checkout')->name('checkout.review');
Route::post('/checkout', [CheckoutController::class, 'place'])->middleware('throttle:checkout')->name('checkout.place');
Route::get('/orders/{order}', [CheckoutController::class, 'show'])->name('orders.show');
Route::get('/orders/{order}/invoice', [CheckoutController::class, 'invoice'])->name('orders.invoice');
Route::post('/orders/{order}/cancel', [CheckoutController::class, 'cancel'])->middleware('throttle:checkout')->name('orders.cancel');
Route::post('/orders/{order}/return', [CheckoutController::class, 'requestReturn'])->middleware('throttle:checkout')->name('orders.return');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /admin\nDisallow: /account\nDisallow: /checkout\nDisallow: /orders\nSitemap: ".route('sitemap'), 200, ['Content-Type' => 'text/plain']));
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => app(AuthController::class)->form('login'))->name('login');
    Route::get('/register', fn () => app(AuthController::class)->form('register'))->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::get('/forgot-password', fn () => app(AuthController::class)->form('forgot'))->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:auth')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:auth')->name('password.update');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/verify-email', fn () => view('auth.verify'))->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::post('/account', [AccountController::class, 'update'])->name('account.update');
    Route::post('/account/addresses', [AccountController::class, 'address'])->name('addresses.store');
    Route::delete('/account/addresses/{address}', [AccountController::class, 'removeAddress'])->name('addresses.destroy');
    Route::get('/wishlist', [StoreController::class, 'wishlist'])->name('wishlist');
    Route::post('/wishlist/{product}', [StoreController::class, 'toggleWishlist'])->name('wishlist.toggle');
    Route::get('/admin-catalog/csv', [CatalogCsvController::class, 'form'])->middleware('verified')->name('csv.form');
    Route::post('/admin-catalog/csv', [CatalogCsvController::class, 'import'])->middleware(['verified', 'throttle:checkout'])->name('csv.import');
    Route::get('/admin-catalog/export', [CatalogCsvController::class, 'export'])->middleware('verified')->name('csv.export');
});
