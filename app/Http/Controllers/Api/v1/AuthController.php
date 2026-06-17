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
     * Helper to generate and dispatch OTP SMS.
     */
    private function dispatchOtp(string $mobile, ?string $payload = null): bool
    {
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
            'payload' => $payload,
        ]);
 
        // Log OTP in development/local
        Log::info("OTP for mobile {$mobile}: {$otpCode}");
 
        // Determine active OTP provider settings
        $otpProvider = \App\Models\Setting::getValue('otp_provider', 'fast2sms');
        $otpRoute = \App\Models\Setting::getValue('otp_route', 'dlt');
        $otpSenderId = \App\Models\Setting::getValue('otp_sender_id', 'FOTPSM');

        if ($otpProvider === 'otpwala') {
            $otpApiUrl = \App\Models\Setting::getValue('otpwala_api_url', 'https://sms.otpwala.com/dev/bulkV2');
            $otpApiKey = \App\Models\Setting::getValue('otpwala_api_key', '');
            $otpTemplateId = \App\Models\Setting::getValue('otpwala_template_id', '12294');
        } else {
            // Default to fast2sms settings (fallback to legacy values if new keys aren't set)
            $otpApiUrl = \App\Models\Setting::getValue('fast2sms_api_url', \App\Models\Setting::getValue('otp_api_url', 'https://www.fast2sms.com/dev/bulkV2'));
            $otpApiKey = \App\Models\Setting::getValue('fast2sms_api_key', \App\Models\Setting::getValue('otp_api_key', 'DEFAULT_OTP_API_KEY'));
            $otpTemplateId = \App\Models\Setting::getValue('fast2sms_template_id', \App\Models\Setting::getValue('otp_template_id', '194943'));
        }

        if ($otpApiKey && $otpApiKey !== 'DEFAULT_OTP_API_KEY') {
            try {
                $response = \Illuminate\Support\Facades\Http::get($otpApiUrl, [
                    'authorization' => $otpApiKey,
                    'route' => $otpRoute,
                    'sender_id' => $otpSenderId,
                    'message' => $otpTemplateId,
                    'variables_values' => $otpCode . '|',
                    'numbers' => $mobile,
                    'schedule_time' => ''
                ]);
                Log::info("Sending SMS via active provider ({$otpProvider}). Status: " . $response->status() . ", Response: " . $response->body());
            } catch (\Exception $e) {
                Log::error("Failed to send OTP SMS via active provider ({$otpProvider}): " . $e->getMessage());
            }
        }
 
        return true;
    }
 
    /**
     * Send OTP to the user's mobile number.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
        ]);
 
        $this->dispatchOtp($request->mobile);
 
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

        // Automatically credit welcome bonus on account creation if enabled
        if (\App\Models\Setting::getValue('welcome_bonus_enabled', '1') === '1') {
            $alreadyCredited = \App\Models\Transaction::where('user_id', $user->id)
                ->where('type', 'incentive')
                ->where('description', 'Welcome Bonus')
                ->exists();
                
            if (!$alreadyCredited) {
                $welcomeAmount = floatval(\App\Models\Setting::getValue('welcome_bonus_amount', '50.00'));
                $welcomeReward = \App\Models\Reward::where('category', 'newbie')->first();
                
                \Illuminate\Support\Facades\DB::transaction(function () use ($user, $welcomeReward, $welcomeAmount) {
                    if ($welcomeReward) {
                        \App\Models\UserReward::create([
                            'user_id' => $user->id,
                            'reward_id' => $welcomeReward->id,
                            'claimed_at' => \Illuminate\Support\Carbon::now()
                        ]);
                    }
                    
                    $user->wallet_balance += $welcomeAmount;
                    $user->total_commission += $welcomeAmount;
                    $user->save();
                    
                    \App\Models\Transaction::create([
                        'user_id' => $user->id,
                        'type' => 'incentive',
                        'amount' => $welcomeAmount,
                        'status' => 'completed',
                        'description' => 'Welcome Bonus'
                    ]);
                });
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration completed successfully.',
            'data' => [
                'user' => $user->fresh()
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
 
    /**
     * Log in a user using mobile and password.
     */
    public function login(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            'password' => ['required', 'string'],
        ]);
 
        $user = User::where('mobile', $request->mobile)->first();
 
        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
                'data' => (object)[]
            ], 422);
        }
 
        if ($user->is_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been blocked by the administrator.',
                'data' => (object)[]
            ], 403);
        }
 
        $token = $user->createToken('auth_token')->plainTextToken;
 
        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'data' => [
                'token' => $token,
                'is_new_user' => false,
                'user' => $user
            ]
        ]);
    }
 
    /**
     * Request a new registration (sends validation OTP).
     */
    public function registerRequest(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10,15}$/', 'unique:users,mobile'],
            'password' => ['required', 'string', 'min:6'],
            'email' => ['nullable', 'email', 'max:255'],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'],
        ], [
            'referral_code.exists' => 'The provided referral code is invalid.',
            'mobile.unique' => 'The mobile number has already been registered.',
        ]);
 
        $payload = json_encode([
            'name' => $request->name,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'email' => $request->email,
            'referral_code' => $request->referral_code
        ]);
 
        $this->dispatchOtp($request->mobile, $payload);
 
        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully for verification.',
            'data' => (object)[]
        ]);
    }
 
    /**
     * Verify registration OTP and create user.
     */
    public function registerVerify(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            'otp' => ['required', 'string', 'size:6'],
        ]);
 
        $mobile = $request->mobile;
        $otpCode = $request->otp;
 
        $otp = Otp::where('mobile', $mobile)
            ->where('otp', $otpCode)
            ->where('verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();
 
        if (!$otp || empty($otp->payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => (object)[]
            ], 422);
        }
 
        $payload = json_decode($otp->payload, true);
        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid registration data.',
                'data' => (object)[]
            ], 422);
        }
 
        // Mark OTP as verified
        $otp->update(['verified' => true]);
 
        // Check if user already exists
        if (User::where('mobile', $mobile)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'The mobile number has already been registered.',
                'data' => (object)[]
            ], 422);
        }
 
        // Create the user
        $user = User::create([
            'mobile' => $mobile,
            'name' => $payload['name'],
            'email' => $payload['email'] ?? null,
            'password' => $payload['password'],
            'wallet_balance' => 0.00,
            'total_investment' => 0.00,
            'total_commission' => 0.00,
            'total_withdrawn' => 0.00,
        ]);
 
        // Generate unique referral code for this user
        do {
            $code = 'CP' . strtoupper(Str::random(6));
        } while (User::where('referral_code', $code)->exists());
        $user->referral_code = $code;
 
        // Process referral link if referrer code was supplied
        if (!empty($payload['referral_code'])) {
            $referrer = User::where('referral_code', $payload['referral_code'])->first();
            if ($referrer && $referrer->id !== $user->id) {
                $user->referred_by = $referrer->id;
            }
        }
        $user->save();

        // Automatically credit welcome bonus on account creation if enabled
        if (\App\Models\Setting::getValue('welcome_bonus_enabled', '1') === '1') {
            $alreadyCredited = \App\Models\Transaction::where('user_id', $user->id)
                ->where('type', 'incentive')
                ->where('description', 'Welcome Bonus')
                ->exists();
                
            if (!$alreadyCredited) {
                $welcomeAmount = floatval(\App\Models\Setting::getValue('welcome_bonus_amount', '50.00'));
                $welcomeReward = \App\Models\Reward::where('category', 'newbie')->first();
                
                \Illuminate\Support\Facades\DB::transaction(function () use ($user, $welcomeReward, $welcomeAmount) {
                    if ($welcomeReward) {
                        \App\Models\UserReward::create([
                            'user_id' => $user->id,
                            'reward_id' => $welcomeReward->id,
                            'claimed_at' => \Illuminate\Support\Carbon::now()
                        ]);
                    }
                    
                    $user->wallet_balance += $welcomeAmount;
                    $user->total_commission += $welcomeAmount;
                    $user->save();
                    
                    \App\Models\Transaction::create([
                        'user_id' => $user->id,
                        'type' => 'incentive',
                        'amount' => $welcomeAmount,
                        'status' => 'completed',
                        'description' => 'Welcome Bonus'
                    ]);
                });
            }
        }
 
        // Create Sanctum Token
        $token = $user->createToken('auth_token')->plainTextToken;
 
        return response()->json([
            'success' => true,
            'message' => 'Registration completed successfully.',
            'data' => [
                'token' => $token,
                'is_new_user' => false,
                'user' => $user->fresh()
            ]
        ]);
    }
 
    /**
     * Send OTP for forget password request.
     */
    public function forgetPassword(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
        ]);
 
        $user = User::where('mobile', $request->mobile)->first();
 
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No user account found with this mobile number.',
                'data' => (object)[]
            ], 422);
        }
 
        $payload = json_encode(['action' => 'password_reset']);
        $this->dispatchOtp($request->mobile, $payload);
 
        return response()->json([
            'success' => true,
            'message' => 'Password reset OTP sent successfully.',
            'data' => (object)[]
        ]);
    }
 
    /**
     * Reset the user password using OTP verification.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            'otp' => ['required', 'string', 'size:6'],
            'new_password' => ['required', 'string', 'min:6'],
        ]);
 
        $mobile = $request->mobile;
        $otpCode = $request->otp;
 
        $otp = Otp::where('mobile', $mobile)
            ->where('otp', $otpCode)
            ->where('verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();
 
        if (!$otp || empty($otp->payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => (object)[]
            ], 422);
        }
 
        $payload = json_decode($otp->payload, true);
        if (!$payload || ($payload['action'] ?? '') !== 'password_reset') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reset action request.',
                'data' => (object)[]
            ], 422);
        }
 
        // Find the user
        $user = User::where('mobile', $mobile)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User account not found.',
                'data' => (object)[]
            ], 422);
        }
 
        // Mark OTP verified
        $otp->update(['verified' => true]);
 
        // Update password
        $user->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        $user->save();
 
        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
            'data' => (object)[]
        ]);
    }

    /**
     * Update the authenticated user's payout details (Bank and/or UPI ID).
     */
    public function updatePayoutDetails(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'ifsc_code' => 'nullable|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'upi_id' => 'nullable|string|max:255',
        ]);

        $bankAdded = !empty($user->account_number);
        $upiAdded = !empty($user->upi_id);

        $wantsToAddBank = $request->filled('account_number') || $request->filled('ifsc_code');
        $wantsToAddUpi = $request->filled('upi_id');

        if ($wantsToAddBank && $bankAdded) {
            if ($user->account_number !== $request->input('account_number') ||
                $user->ifsc_code !== $request->input('ifsc_code') ||
                $user->account_holder_name !== $request->input('account_holder_name')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank details are already added. Contact support to update them.',
                    'data' => (object)[]
                ], 422);
            }
        }

        if ($wantsToAddUpi && $upiAdded) {
            if ($user->upi_id !== $request->input('upi_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'UPI ID is already added. Contact support to update it.',
                    'data' => (object)[]
                ], 422);
            }
        }

        if ($wantsToAddBank && !$bankAdded) {
            $ifsc = strtoupper(trim($request->input('ifsc_code')));
            
            if (empty($ifsc) || strlen($ifsc) !== 11) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a valid 11-digit IFSC code.',
                    'data' => (object)[]
                ], 422);
            }
            
            try {
                $response = \Illuminate\Support\Facades\Http::get("https://ifsc.razorpay.com/" . $ifsc);
                if (!$response->successful() || trim($response->body()) === 'Not Found') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid IFSC Code. Please check and try again.',
                        'data' => (object)[]
                    ], 422);
                }
                
                $ifscData = $response->json();
                $bankName = $ifscData['BANK'] ?? null;
                
                if (empty($bankName)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Could not determine bank name from the provided IFSC.',
                        'data' => (object)[]
                    ], 422);
                }
                
                $user->bank_name = $bankName;
                $user->account_number = $request->input('account_number');
                $user->ifsc_code = $ifsc;
                $user->account_holder_name = $request->input('account_holder_name');
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify IFSC code: ' . $e->getMessage(),
                    'data' => (object)[]
                ], 422);
            }
        }

        if ($wantsToAddUpi && !$upiAdded) {
            $user->upi_id = $request->input('upi_id');
        }

        $user->save();

        $activeOrders = Order::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();
            
        $activePlansCount = $activeOrders->count();
        $totalActiveInvested = $activeOrders->sum('amount');
        
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Payout details updated successfully.',
            'data' => [
                'user' => $user,
                'active_plans_count' => $activePlansCount,
                'total_active_invested' => number_format($totalActiveInvested, 2, '.', ''),
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }
}
