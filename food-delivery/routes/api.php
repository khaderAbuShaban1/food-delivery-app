<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\DriverOrderController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\RestaurantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// User auth routes (public) - for Flutter app
Route::post('/user/register', [AuthController::class, 'register']);
Route::post('/user/login', [AuthController::class, 'login']);
Route::post('/user/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/user/resend-verification', [AuthController::class, 'resendEmailVerification']);
Route::post('/driver/register', [DriverAuthController::class, 'register']);
Route::post('/driver/login', [DriverAuthController::class, 'login']);
Route::post('/driver/verify-email', [DriverAuthController::class, 'verifyEmail']);
Route::post('/driver/resend-verification', [DriverAuthController::class, 'resendEmailVerification']);

Route::middleware('auth:sanctum')->prefix('driver')->group(function () {
    Route::get('/orders/available-pool', [DriverOrderController::class, 'availablePool']);
    Route::get('/orders/active', [DriverOrderController::class, 'activeOrder']);
    Route::post('/orders/{id}/accept', [DriverOrderController::class, 'accept']);
    Route::put('/orders/{id}/status', [DriverOrderController::class, 'updateStatus']);
    Route::patch('/orders/{id}/status', [DriverOrderController::class, 'updateStatus']);
    Route::get('/stats/today', [DriverOrderController::class, 'todayStats']);
    Route::get('/orders/history', [DriverOrderController::class, 'history']);
});

// Public restaurant routes
Route::get('/restaurants', [RestaurantController::class, 'index']);
Route::get('/restaurants/{id}', [RestaurantController::class, 'show']);

// Test route  
Route::get('/test', function () {
    return ['success' => true, 'message' => 'API working'];
});

// Menu route  
Route::get('/restaurants/{id}/menu', [MenuController::class, 'publicIndex']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/profile/image', [AuthController::class, 'uploadProfileImage']);
    Route::post('/restaurants/{id}/rate', [RestaurantController::class, 'rate']);

    // User addresses
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

    Route::get('/payment-methods/active', [PaymentMethodController::class, 'active']);
    
    // User orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    
    // Restaurant order management
    Route::get('/restaurant/orders', [OrderController::class, 'restaurantOrders']);
    Route::put('/restaurant/orders/{id}/status', [OrderController::class, 'restaurantUpdateStatus']);
});
