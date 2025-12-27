
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\PlanController;

// Frontend Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{slug}', [ShopController::class, 'showCategory'])->name('shop.category');

// Page Routes
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('page.show');

// Checkout Routes
Route::get('/checkout/{plan}', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout/{plan}', [CheckoutController::class, 'process'])->name('checkout.process')->middleware('auth');

// Dashboard Routes (requires auth)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/orders', [DashboardController::class, 'orders'])->name('dashboard.orders');
});

// Admin Routes
Route::middleware(['auth', 'admin', 'admin.audit', 'throttle:' . config('rate-limiting.api.max_attempts') . ',' . config('rate-limiting.api.decay_minutes')])->prefix('admin')->group(function () {
    // Pages Management
    Route::resource('pages', AdminPageController::class)->middleware('permission:manage-pages');

    // Categories Management
    Route::resource('categories', ServiceCategoryController::class)->middleware('permission:manage-categories');

    // Plans Management
    Route::resource('plans', PlanController::class)->middleware('permission:manage-plans');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', function () {
        // Laravel Breeze or custom authentication
        return redirect('/');
    })->middleware('throttle:login');

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::post('/register', function () {
        // Laravel Breeze or custom registration
        return redirect('/');
    });
});

Route::post('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/');
})->name('logout')->middleware('auth');
