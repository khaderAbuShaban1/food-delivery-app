<?php

use App\Http\Controllers\Restaurant\AuthController;
use App\Http\Controllers\Restaurant\DashboardController;
use App\Http\Controllers\Restaurant\MenuController;
use App\Http\Controllers\Restaurant\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/restaurant/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/restaurant/login', [AuthController::class, 'showLoginForm'])->name('restaurant.login');
    Route::post('/restaurant/login', [AuthController::class, 'login']);
});

Route::middleware('auth', 'isRestaurant')->group(function () {
    Route::get('/restaurant/dashboard', [DashboardController::class, 'index'])->name('restaurant.dashboard');
    Route::patch('/restaurant/dashboard/status', [DashboardController::class, 'updateStatus'])->name('restaurant.dashboard.status');
    Route::post('/restaurant/logout', [AuthController::class, 'logout'])->name('restaurant.logout');

    Route::get('/restaurant/menu', [MenuController::class, 'index'])->name('restaurant.menu');
    Route::post('/restaurant/menu', [MenuController::class, 'store']);
    Route::put('/restaurant/menu/{id}', [MenuController::class, 'update']);
    Route::delete('/restaurant/menu/{id}', [MenuController::class, 'destroy']);

    Route::get('/restaurant/orders', [OrderController::class, 'index'])->name('restaurant.orders');
    Route::put('/restaurant/orders/{id}/status', [OrderController::class, 'updateStatus']);
});