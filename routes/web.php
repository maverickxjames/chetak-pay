<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\WebPaymentController;
use App\Models\Setting;

Route::get('/pay/{order_id}', [WebPaymentController::class, 'showPayPage'])->name('payment.share');
Route::post('/pay/{order_id}/verify', [WebPaymentController::class, 'verifyPayPage'])->name('payment.share.verify');

Route::get('/', function () {
    $settings = [
        'website_name' => Setting::getValue('website_name', 'Chetak Pay'),
        'website_logo' => Setting::getValue('website_logo', ''),
        'support_contact' => Setting::getValue('support_contact', 'support@chetakpay.com'),
        'feature_referrals' => Setting::getValue('feature_referrals', '1'),
        'feature_rewards' => Setting::getValue('feature_rewards', '1'),
        'app_version' => Setting::getValue('app_version', '1.0.0'),
        'app_update_url' => Setting::getValue('app_update_url', '#'),
        'about_us' => Setting::getValue('about_us', 'Chetak Pay is a high-speed secure payment and investment platform. Grow your funds instantly with our direct commissions plan.'),
        'privacy_policy' => Setting::getValue('privacy_policy', 'Please read our privacy policy details carefully. We secure your credentials.'),
        'terms_conditions' => Setting::getValue('terms_conditions', 'Please read our terms and conditions. Direct 3% commission is added instantly on plan approval.'),
    ];
    return view('welcome', compact('settings'));
});

Route::prefix('admin')->group(function () {
    // Guest Routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login');
    });

    // Authenticated Admin Routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('admin.dashboard');
        
        // Users Management
        Route::get('/users', [AdminDashboardController::class, 'users'])->name('admin.users');
        Route::post('/users/{id}/adjust-balance', [AdminDashboardController::class, 'adjustBalance'])->name('admin.users.adjust');
        Route::post('/users/{id}/toggle-status', [AdminDashboardController::class, 'toggleUserStatus'])->name('admin.users.toggle');
        Route::post('/users/{id}/admin-withdraw', [AdminDashboardController::class, 'adminWithdraw'])->name('admin.users.withdraw');

        // Gateway Settings
        Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('admin.settings');
        Route::post('/settings', [AdminDashboardController::class, 'saveSettings'])->name('admin.settings.save');

        // Activity Logs
        Route::get('/logs', [AdminDashboardController::class, 'logs'])->name('admin.logs');

        // Admin Roles/Accounts Management
        Route::get('/admins', [AdminDashboardController::class, 'admins'])->name('admin.admins');
        Route::post('/admins', [AdminDashboardController::class, 'storeAdmin'])->name('admin.admins.store');

        // Logged-in Admin Profile & Password Change
        Route::get('/profile', [AdminDashboardController::class, 'profile'])->name('admin.profile');
        Route::post('/profile/password', [AdminDashboardController::class, 'changePassword'])->name('admin.profile.password');
        
        // Orders Management
        Route::get('/orders', [AdminDashboardController::class, 'orders'])->name('admin.orders');
        Route::post('/orders/{id}/approve', [AdminDashboardController::class, 'approveOrder'])->name('admin.orders.approve');
        Route::post('/orders/{id}/cancel', [AdminDashboardController::class, 'cancelOrder'])->name('admin.orders.cancel');
        
        // Plans Management
        Route::get('/plans', [AdminDashboardController::class, 'plans'])->name('admin.plans');
        Route::post('/plans', [AdminDashboardController::class, 'storePlan'])->name('admin.plans.store');
        Route::post('/plans/{id}/toggle', [AdminDashboardController::class, 'togglePlan'])->name('admin.plans.toggle');
        
        // Notifications / Announcements
        Route::get('/notifications', [AdminDashboardController::class, 'notifications'])->name('admin.notifications');
        Route::post('/notifications', [AdminDashboardController::class, 'storeNotification'])->name('admin.notifications.store');
    });
});
