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
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register/request', [AuthController::class, 'registerRequest']);
    Route::post('/register/verify', [AuthController::class, 'registerVerify']);
    Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/config', [\App\Http\Controllers\Api\v1\ConfigController::class, 'getConfig']);
    
    Route::middleware(['auth:sanctum', 'check_blocked'])->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/profile/fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::post('/profile/payout-details', [AuthController::class, 'updatePayoutDetails']);
        
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
