<?php

use App\Modules\Admin\Controllers\AuthController;
use App\Modules\Admin\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [DashboardController::class, 'users'])->name('users');
        
        Route::get('/restaurants', [DashboardController::class, 'restaurants'])->name('restaurants');
        Route::post('/restaurants', [DashboardController::class, 'storeRestaurant'])->name('restaurants.store');
        Route::put('/restaurants/{id}', [DashboardController::class, 'updateRestaurant'])->name('restaurants.update');
        Route::delete('/restaurants/{id}', [DashboardController::class, 'deleteRestaurant'])->name('restaurants.destroy');
        Route::patch('/restaurants/{id}/toggle-status', [DashboardController::class, 'toggleActive'])->name('restaurants.toggle-status');
        Route::patch('/restaurants/{id}/toggle-open', [DashboardController::class, 'toggleOpenStatus'])->name('restaurants.toggle-open');
        Route::patch('/restaurants/{id}/toggle', [DashboardController::class, 'toggleRestaurant'])->name('restaurants.toggle');
        
        Route::get('/menu', [DashboardController::class, 'menu'])->name('menu');
        Route::get('/orders', [DashboardController::class, 'orders'])->name('orders');
        Route::get('/offers', [DashboardController::class, 'offers'])->name('offers');
    });
});