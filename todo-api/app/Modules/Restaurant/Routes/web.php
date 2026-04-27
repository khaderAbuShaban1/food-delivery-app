<?php

use App\Modules\Restaurant\Controllers\AuthController;
use App\Modules\Restaurant\Controllers\DashboardController;
use App\Modules\Restaurant\Controllers\MenuController;
use App\Modules\Restaurant\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('restaurant')->name('restaurant.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware('auth.restaurant')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::patch('/dashboard/status/{id}', [DashboardController::class, 'updateStatus'])->name('dashboard.status');

        Route::get('/menu', [MenuController::class, 'index'])->name('menu');
        Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
        Route::put('/menu/{restaurantId}/{menuItemId}', [MenuController::class, 'update'])->name('menu.update');
        Route::delete('/menu/{restaurantId}/{menuItemId}', [MenuController::class, 'destroy'])->name('menu.destroy');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders');
        Route::put('/orders/{orderId}', [OrderController::class, 'updateStatus'])->name('orders.status');

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});