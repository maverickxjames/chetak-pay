<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\RewardController;
use App\Http\Controllers\Api\v1\WalletController;
use App\Http\Controllers\Api\v1\TeamController;
use App\Http\Controllers\Api\v1\NotificationController;
use App\Http\Controllers\Api\v1\OrderController;

Route::prefix('v1')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::get('/config', [\App\Http\Controllers\Api\v1\ConfigController::class, 'getConfig']);
    
    Route::middleware(['auth:sanctum', 'check_blocked'])->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/profile/fcm-token', [AuthController::class, 'updateFcmToken']);
        
        // Phase 3 endpoints
        Route::get('/rewards', [RewardController::class, 'index']);
        Route::post('/rewards/{id}/claim', [RewardController::class, 'claim']);
        Route::get('/wallet', [WalletController::class, 'index']);
        Route::get('/team', [TeamController::class, 'index']);
        Route::get('/notifications', [NotificationController::class, 'index']);

        // Phase 4 endpoints
        Route::get('/plans', [OrderController::class, 'getPlans']);
        Route::get('/orders', [OrderController::class, 'getOrders']);
        Route::post('/orders', [OrderController::class, 'createOrder']);
        Route::post('/orders/{id}/verify', [OrderController::class, 'verifyPayment']);
    });
});
