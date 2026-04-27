<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MenuController;
use App\Http\Controllers\Web\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('isRestaurant')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::patch('/dashboard/status/{id}', [DashboardController::class, 'updateStatus'])->name('dashboard.status');
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/menu', [MenuController::class, 'index'])->name('menu');
    Route::post('/menu', [MenuController::class, 'store']);
    Route::put('/menu/{restaurantId}/{menuItemId}', [MenuController::class, 'update'])->name('menu.update');
    Route::delete('/menu/{restaurantId}/{menuItemId}', [MenuController::class, 'destroy'])->name('menu.destroy');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::put('/orders/{orderId}', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
});