<?php

use App\Modules\Restaurant\Controllers\AuthController;
use App\Modules\Restaurant\Controllers\DashboardController;
use App\Modules\Restaurant\Controllers\MenuController;
use App\Modules\Restaurant\Controllers\OrderController;
use App\Modules\Restaurant\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('restaurant')->name('restaurant.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::middleware('auth.restaurant')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/realtime', [DashboardController::class, 'realtime'])->name('dashboard.realtime');
        Route::patch('/dashboard/status/{id}', [DashboardController::class, 'updateStatus'])->name('dashboard.status');

        Route::get('/menu', [MenuController::class, 'index'])->name('menu');
        Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
        Route::put('/menu/{restaurantId}/{menuItemId}', [MenuController::class, 'update'])->name('menu.update');
        Route::delete('/menu/{restaurantId}/{menuItemId}', [MenuController::class, 'destroy'])->name('menu.destroy');
        Route::patch('/menu/{menuItemId}/toggle-availability', [MenuController::class, 'toggleAvailability'])->name('menu.toggle-availability');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders');
        Route::get('/orders/realtime', [OrderController::class, 'realtime'])->name('orders.realtime');
        Route::put('/orders/{orderId}', [OrderController::class, 'updateStatus'])->name('orders.status');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('/settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
        Route::put('/settings/working-hours', [SettingsController::class, 'updateWorkingHours'])->name('settings.working-hours');
        Route::put('/settings/orders', [SettingsController::class, 'updateOrderSettings'])->name('settings.orders');
        Route::put('/settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account');

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});