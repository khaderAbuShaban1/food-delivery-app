<?php

use App\Modules\Admin\Controllers\AuthController;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Admin\Controllers\DebugMailController;
use App\Modules\Admin\Controllers\PaymentMethodController;
use App\Modules\Admin\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/realtime', [DashboardController::class, 'realtime'])->name('dashboard.realtime');
        Route::get('/users', [DashboardController::class, 'users'])->name('users');
        Route::get('/users/{id}', [DashboardController::class, 'getUser'])->name('users.show');
        Route::post('/users/{id}/ban', [DashboardController::class, 'toggleUserStatusApi'])->name('users.ban');
        Route::post('/users/{id}/toggle', [DashboardController::class, 'toggleUserStatus'])->name('users.toggle');
        Route::get('/drivers', [DashboardController::class, 'drivers'])->name('drivers.index');
        Route::patch('/drivers/{id}/approve', [DashboardController::class, 'approveDriver'])->name('drivers.approve');
        Route::patch('/drivers/{id}/reject', [DashboardController::class, 'rejectDriver'])->name('drivers.reject');

        Route::post('/debug/mail-test', [DebugMailController::class, 'send'])->name('debug.mail-test');
        
        Route::get('/restaurants', [DashboardController::class, 'restaurants'])->name('restaurants');
        Route::post('/restaurants', [DashboardController::class, 'storeRestaurant'])->name('restaurants.store');
        Route::put('/restaurants/{id}', [DashboardController::class, 'updateRestaurant'])->name('restaurants.update');
        Route::delete('/restaurants/{id}', [DashboardController::class, 'deleteRestaurant'])->name('restaurants.destroy');
        Route::patch('/restaurants/{id}/toggle-status', [DashboardController::class, 'toggleActive'])->name('restaurants.toggle-status');
        Route::patch('/restaurants/{id}/toggle-open', [DashboardController::class, 'toggleOpenStatus'])->name('restaurants.toggle-open');
        Route::patch('/restaurants/{id}/toggle', [DashboardController::class, 'toggleRestaurant'])->name('restaurants.toggle');
        
        Route::get('/menu', [DashboardController::class, 'menu'])->name('menu');
        Route::post('/menu/{id}', [DashboardController::class, 'updateMenuItem'])->name('menu.update');
        Route::post('/menu/{id}/delete', [DashboardController::class, 'deleteMenuItem'])->name('menu.destroy');
        Route::get('/orders', [DashboardController::class, 'orders'])->name('orders');
        Route::get('/orders/list', [DashboardController::class, 'getOrders'])->name('orders.list');
        Route::get('/orders/{id}/data', [DashboardController::class, 'getOrderData'])->name('orders.data');
        Route::patch('/orders/{id}/verify-payment', [DashboardController::class, 'verifyOrderPayment'])->name('orders.verify-payment');
        Route::patch('/orders/{id}/reject-payment', [DashboardController::class, 'rejectOrderPayment'])->name('orders.reject-payment');
        Route::get('/offers', [DashboardController::class, 'offers'])->name('offers');

        Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::put('/payment-methods/{payment_method}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
        Route::delete('/payment-methods/{payment_method}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
        Route::patch('/payment-methods/{payment_method}/toggle', [PaymentMethodController::class, 'toggle'])->name('payment-methods.toggle');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('/settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
        Route::put('/settings/platform', [SettingsController::class, 'updatePlatform'])->name('settings.platform');
        Route::put('/settings/payment', [SettingsController::class, 'updatePayment'])->name('settings.payment');
        Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
        Route::put('/settings/security', [SettingsController::class, 'updateSecurity'])->name('settings.security');
    });
});
