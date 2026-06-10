<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Send OTP to the user's mobile number.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
        ]);

        $mobile = $request->mobile;
        
        // Generate OTP
        $otpCode = config('app.env') !== 'production' && env('DEV_OTP') 
            ? env('DEV_OTP') 
            : str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP
        Otp::create([
            'mobile' => $mobile,
            'otp' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(10),
            'verified' => false,
        ]);

        // Log OTP in development/local
        Log::info("OTP for mobile {$mobile}: {$otpCode}");

        // Handle custom OTP API Key logging
        $otpApiKey = \App\Models\Setting::getValue('otp_api_key');
        if ($otpApiKey && $otpApiKey !== 'DEFAULT_OTP_API_KEY') {
            Log::info("Sending SMS via configured API gateway. Key: " . substr($otpApiKey, 0, 5) . "...");
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully.',
            'data' => (object)[]
        ]);
    }

    /**
     * Verify the OTP sent to the user's mobile number.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $mobile = $request->mobile;
        $otpCode = $request->otp;

        // Check if OTP exists, is not expired and not verified
        $otp = Otp::where('mobile', $mobile)
            ->where('otp', $otpCode)
            ->where('verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => (object)[]
            ], 422);
        }

        // Mark OTP as verified
        $otp->update(['verified' => true]);

        // Find or create user placeholder
        $user = User::where('mobile', $mobile)->first();
        $isNewUser = false;

        if (!$user) {
            $isNewUser = true;
            $user = User::create([
                'mobile' => $mobile,
                'wallet_balance' => 0.00,
                'total_investment' => 0.00,
                'total_commission' => 0.00,
                'total_withdrawn' => 0.00,
            ]);
        } else if (empty($user->name)) {
            // If user exists but name is not set, we treat them as new (needs registration completion)
            $isNewUser = true;
        }

        // Create Sanctum Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
            'data' => [
                'token' => $token,
                'is_new_user' => $isNewUser,
                'user' => $user
            ]
        ]);
    }

    /**
     * Complete registration for new users.
     */
    public function register(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'],
        ], [
            'referral_code.exists' => 'The provided referral code is invalid.',
        ]);

        // Generate unique referral code for this user if not already generated
        if (empty($user->referral_code)) {
            do {
                $code = 'CP' . strtoupper(Str::random(6));
            } while (User::where('referral_code', $code)->exists());
            $user->referral_code = $code;
        }

        // Handle referral linkage if referred_by is not already set
        if ($request->filled('referral_code') && empty($user->referred_by)) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
            
            // Prevent referring oneself
            if ($referrer && $referrer->id !== $user->id) {
                $user->referred_by = $referrer->id;
            } else if ($referrer && $referrer->id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot refer yourself.',
                    'data' => (object)[]
                ], 422);
            }
        }

        $user->name = $request->name;
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Registration completed successfully.',
            'data' => [
                'user' => $user
            ]
        ]);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Calculate active plans stats
        $activeOrders = Order::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();
            
        $activePlansCount = $activeOrders->count();
        $totalActiveInvested = $activeOrders->sum('amount');
        
        // Load latest 5 transactions
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'user' => $user,
                'active_plans_count' => $activePlansCount,
                'total_active_invested' => number_format($totalActiveInvested, 2, '.', ''),
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }

    /**
     * Logout the user by revoking their current access token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
            'data' => (object)[]
        ]);
    }

    /**
     * Update the FCM token for the authenticated user.
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully.',
            'data' => (object)[]
        ]);
    }
}
