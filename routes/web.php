<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CallCenter;
use App\Http\Controllers\Customer;
use App\Http\Controllers\Driver;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Realtime\OrderPulseController;
use Illuminate\Support\Facades\Route;

Route::get('/', Customer\HomeController::class)->name('customer.home');
Route::get('/search', Customer\SearchController::class)->name('customer.search');
Route::get('/businesses/{business:slug}', [Customer\BusinessController::class, 'show'])->name('customer.businesses.show');
Route::get('/products/{product}/options', [Customer\ProductController::class, 'options'])
    ->middleware('throttle:60,1')
    ->name('customer.products.options');
Route::get('/location/reverse', [Customer\LocationController::class, 'reverse'])->middleware('throttle:30,1')->name('customer.location.reverse');
Route::get('/location/search', [Customer\LocationController::class, 'search'])->middleware('throttle:30,1')->name('customer.location.search');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->middleware('throttle:20,1')
    ->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware('throttle:20,1')
    ->name('auth.google.callback');

Route::post('/logout', [LogoutController::class, 'store'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->middleware('throttle:password-update')
        ->name('profile.password.update');
    Route::delete('/auth/google', [GoogleAuthController::class, 'unlink'])
        ->middleware('throttle:20,1')
        ->name('auth.google.unlink');

    Route::get('/realtime/orders/pulse', OrderPulseController::class)
        ->middleware('throttle:120,1')
        ->name('realtime.orders.pulse');
});

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::middleware('permission:customer.cart')->group(function () {
        Route::get('/cart', [Customer\CartController::class, 'show'])->name('customer.cart');
        Route::post('/cart', [Customer\CartController::class, 'store'])->name('customer.cart.store');
        Route::patch('/cart/items/{cartItem}', [Customer\CartController::class, 'update'])->name('customer.cart.update');
        Route::delete('/cart/items/{cartItem}', [Customer\CartController::class, 'destroy'])->name('customer.cart.destroy');
    });
    Route::post('/cart/coupon', [Customer\CartController::class, 'applyCoupon'])
        ->middleware('permission:customer.coupons')
        ->name('customer.cart.coupon');
    Route::middleware('permission:customer.checkout')->group(function () {
        Route::get('/checkout', [Customer\CheckoutController::class, 'create'])->name('customer.checkout');
        Route::post('/checkout', [Customer\CheckoutController::class, 'store'])->name('customer.checkout.store');
    });
    Route::middleware('permission:customer.orders')->group(function () {
        Route::get('/orders', [Customer\OrderController::class, 'index'])->name('customer.orders.index');
        Route::get('/orders/{order}', [Customer\OrderController::class, 'show'])->name('customer.orders.show');
    });
    Route::post('/orders/{order}/reorder', [Customer\OrderController::class, 'reorder'])
        ->middleware('permission:customer.reorder')
        ->name('customer.orders.reorder');
    Route::post('/orders/{order}/cancel', [Customer\OrderController::class, 'cancel'])
        ->middleware('permission:customer.cancel_order')
        ->name('customer.orders.cancel');
    Route::get('/account', [ProfileController::class, 'edit'])->name('customer.profile');
    Route::patch('/account', [ProfileController::class, 'update'])->name('customer.profile.update');
    Route::put('/account/password', [ProfileController::class, 'updatePassword'])
        ->middleware('throttle:password-update')
        ->name('customer.profile.password');
    Route::middleware('permission:customer.addresses')->group(function () {
        Route::get('/addresses', [Customer\AddressController::class, 'index'])->name('customer.addresses.index');
        Route::post('/addresses', [Customer\AddressController::class, 'store'])->name('customer.addresses.store');
        Route::patch('/addresses/{address}', [Customer\AddressController::class, 'update'])->name('customer.addresses.update');
        Route::delete('/addresses/{address}', [Customer\AddressController::class, 'destroy'])->name('customer.addresses.destroy');
    });
});

Route::middleware(['auth', 'role:super_admin|admin|business_manager'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/transition', [Admin\OrderController::class, 'transition'])->name('orders.transition');
    Route::post('/orders/{order}/cancel', [Admin\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::delete('/orders/{order}', [Admin\OrderController::class, 'destroy'])->name('orders.destroy');
    Route::post('/orders/{order}/assign', [Admin\OrderController::class, 'assign'])->name('orders.assign');
    Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [Admin\CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{user}', [Admin\CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{user}/block', [Admin\CustomerController::class, 'block'])->name('customers.block');
    Route::post('/customers/{user}/unblock', [Admin\CustomerController::class, 'unblock'])->name('customers.unblock');
    Route::delete('/customers/{user}', [Admin\CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::get('/businesses', [Admin\BusinessController::class, 'index'])->name('businesses.index');
    Route::get('/businesses/create', [Admin\BusinessController::class, 'create'])->name('businesses.create');
    Route::post('/businesses', [Admin\BusinessController::class, 'store'])->name('businesses.store');
    Route::get('/businesses/{business}', [Admin\BusinessController::class, 'show'])->name('businesses.show');
    Route::get('/businesses/{business}/edit', [Admin\BusinessController::class, 'edit'])->name('businesses.edit');
    Route::patch('/businesses/{business}', [Admin\BusinessController::class, 'update'])->name('businesses.update');
    Route::post('/businesses/{business}/cover', [Admin\BusinessController::class, 'storeCover'])->name('businesses.cover.store');
    Route::post('/businesses/{business}/branches', [Admin\BusinessController::class, 'storeBranch'])->name('businesses.branches.store');
    Route::post('/businesses/{business}/product-categories', [Admin\BusinessController::class, 'storeCategory'])->name('businesses.product-categories.store');
    Route::patch('/businesses/{business}/product-categories/{productCategory}', [Admin\BusinessController::class, 'updateCategory'])->name('businesses.product-categories.update')->scopeBindings();
    Route::delete('/businesses/{business}/product-categories/{productCategory}', [Admin\BusinessController::class, 'destroyCategory'])->name('businesses.product-categories.destroy')->scopeBindings();
    Route::post('/businesses/{business}/products', [Admin\BusinessController::class, 'storeProduct'])->name('businesses.products.store');
    Route::patch('/businesses/{business}/products/{product}', [Admin\BusinessController::class, 'updateProduct'])->name('businesses.products.update')->scopeBindings();
    Route::patch('/businesses/{business}/products/{product}/availability', [Admin\BusinessController::class, 'toggleProductAvailability'])->name('businesses.products.availability')->scopeBindings();
    Route::delete('/businesses/{business}/products/{product}', [Admin\BusinessController::class, 'destroyProduct'])->name('businesses.products.destroy')->scopeBindings();
    Route::get('/categories', [Admin\BusinessCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [Admin\BusinessCategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{businessCategory}', [Admin\BusinessCategoryController::class, 'update'])->name('categories.update');
    Route::get('/products', [Admin\ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [Admin\ProductController::class, 'store'])->name('products.store');
    Route::patch('/products/{product}', [Admin\ProductController::class, 'update'])->name('products.update');
    Route::post('/product-categories', [Admin\ProductCategoryController::class, 'store'])->name('product-categories.store');
    Route::get('/coupons', [Admin\CouponController::class, 'index'])->name('coupons.index');
    Route::post('/coupons', [Admin\CouponController::class, 'store'])->name('coupons.store');
    Route::patch('/coupons/{coupon}', [Admin\CouponController::class, 'update'])->name('coupons.update');
    Route::get('/zones', [Admin\DeliveryZoneController::class, 'index'])->name('zones.index');
    Route::post('/zones', [Admin\DeliveryZoneController::class, 'store'])->name('zones.store');
    Route::delete('/zones/{deliveryZone}', [Admin\DeliveryZoneController::class, 'destroy'])->name('zones.destroy');
    Route::post('/zones/{deliveryZone}/rules', [Admin\DeliveryZoneController::class, 'storeRule'])->name('zones.rules.store');
    Route::patch('/zone-rules/{deliveryFeeRule}', [Admin\DeliveryZoneController::class, 'updateRule'])->name('zones.rules.update');
    Route::delete('/zone-rules/{deliveryFeeRule}', [Admin\DeliveryZoneController::class, 'destroyRule'])->name('zones.rules.destroy');
    Route::get('/drivers', [Admin\DriverController::class, 'index'])->name('drivers.index');
    Route::post('/drivers', [Admin\DriverController::class, 'store'])->name('drivers.store');
    Route::patch('/drivers/{driver}', [Admin\DriverController::class, 'update'])->name('drivers.update');
    Route::get('/staff', [Admin\StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [Admin\StaffController::class, 'store'])->name('staff.store');
    Route::patch('/staff/{staff}', [Admin\StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staff}', [Admin\StaffController::class, 'destroy'])->name('staff.destroy');
    Route::get('/roles', [Admin\RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [Admin\RoleController::class, 'store'])->name('roles.store');
    Route::patch('/roles/{role}', [Admin\RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [Admin\RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/permissions', [Admin\PermissionController::class, 'store'])->name('permissions.store');
    Route::delete('/permissions/{permission}', [Admin\PermissionController::class, 'destroy'])->name('permissions.destroy');
    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');
    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::get('/audit-logs', Admin\AuditLogController::class)->name('audit-logs.index');
});

Route::middleware(['auth', 'role:super_admin|admin|call_center_agent|dispatcher'])->prefix('dispatch')->name('call-center.')->group(function () {
    Route::get('/', CallCenter\DashboardController::class)->name('dashboard');
    Route::get('/customers/search', [CallCenter\CustomerController::class, 'search'])->name('customers.search');
    Route::post('/customers', [CallCenter\CustomerController::class, 'store'])->name('customers.store');
    Route::post('/customers/{user}/addresses', [CallCenter\CustomerController::class, 'storeAddress'])->name('customers.addresses.store');
    Route::get('/orders/create', [CallCenter\OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [CallCenter\OrderController::class, 'store'])->name('orders.store');
    Route::get('/businesses/{business}/products', [CallCenter\OrderController::class, 'products'])->name('businesses.products');
    Route::get('/orders/{order}', [CallCenter\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/confirm', [CallCenter\OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('/orders/{order}/reject', [CallCenter\OrderController::class, 'reject'])->name('orders.reject');
    Route::post('/orders/{order}/assign', [CallCenter\OrderController::class, 'assign'])->name('orders.assign');
    Route::post('/orders/{order}/note', [CallCenter\OrderController::class, 'note'])->name('orders.note');
    Route::post('/orders/{order}/transition', [CallCenter\OrderController::class, 'transition'])->name('orders.transition');
});

Route::middleware(['auth', 'role:driver'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/', Driver\DashboardController::class)->name('dashboard');
    Route::get('/history', [Driver\OrderController::class, 'history'])->name('history');
    Route::get('/orders/{order}', [Driver\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/pickup', [Driver\OrderController::class, 'pickup'])->name('orders.pickup');
    Route::post('/orders/{order}/out', [Driver\OrderController::class, 'outForDelivery'])->name('orders.out');
    Route::post('/orders/{order}/deliver', [Driver\OrderController::class, 'deliver'])->name('orders.deliver');
    Route::post('/orders/{order}/fail', [Driver\OrderController::class, 'fail'])->name('orders.fail');
});
