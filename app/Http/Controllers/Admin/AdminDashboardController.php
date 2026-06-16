<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\AdminLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    /**
     * Helper to log admin actions.
     */
    private function logAction(string $action, ?string $details = null)
    {
        AdminLog::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Display the Admin Dashboard metrics and activity charts.
     */
    public function dashboard()
    {
        // Total Stats
        $totalInvestments = Order::where('status', 'active')->sum('amount');
        $totalCommissions = Transaction::where('type', 'commission_credit')->sum('amount');
        $totalWithdrawn = Transaction::where('type', 'withdrawal')->sum('amount');
        $totalUsers = User::where('is_admin', false)->count();

        // Today's Stats
        $todayInvestments = Order::where('status', 'active')->whereDate('completed_at', today())->sum('amount');
        $todayCommissions = Transaction::where('type', 'commission_credit')->whereDate('created_at', today())->sum('amount');
        $todayWithdrawn = Transaction::where('type', 'withdrawal')->whereDate('created_at', today())->sum('amount');
        $todayUsers = User::where('is_admin', false)->whereDate('created_at', today())->count();

        $recentTransactions = Transaction::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentUsers = User::where('is_admin', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalInvestments',
            'totalCommissions',
            'totalWithdrawn',
            'totalUsers',
            'todayInvestments',
            'todayCommissions',
            'todayWithdrawn',
            'todayUsers',
            'recentTransactions',
            'recentUsers'
        ));
    }

    /**
     * Display list of users.
     */
    public function users(Request $request)
    {
        $query = User::where('is_admin', false);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users', compact('users'));
    }

    /**
     * Toggle a user's block status.
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->is_admin) {
            return back()->withErrors(['error' => 'Cannot modify administrator status.']);
        }

        $user->is_blocked = !$user->is_blocked;
        $user->save();

        if ($user->is_blocked) {
            $user->tokens()->delete(); // Revoke all mobile tokens instantly
            $action = 'Block User';
        } else {
            $action = 'Unblock User';
        }

        $this->logAction($action, "Blocked User ID: {$user->id}, Mobile: {$user->mobile}");

        return back()->with('success', "User has been " . ($user->is_blocked ? 'blocked' : 'unblocked') . " successfully.");
    }

    /**
     * Custom admin withdrawal for a user.
     */
    public function adminWithdraw(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'message' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($id);
        $amount = (float) $request->input('amount');
        $message = $request->input('message');

        if ($user->wallet_balance < $amount) {
            return back()->withErrors(['amount' => 'Insufficient wallet balance for withdrawal deduction.']);
        }

        DB::transaction(function () use ($user, $amount, $message) {
            $user->wallet_balance -= $amount;
            $user->total_withdrawn += $amount;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'amount' => $amount,
                'status' => 'completed',
                'description' => "Admin Withdrawal: " . $message,
            ]);
        });

        $this->logAction('Admin Withdraw User Funds', "User ID: {$user->id}, Amount: {$amount}, Reason: {$message}");

        return back()->with('success', 'Withdrawal deduction completed successfully.');
    }

    /**
     * Manually adjust a user's wallet balance.
     */
    public function adjustBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:credit,debit',
            'description' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($id);
        $amount = (float) $request->input('amount');

        if ($request->input('type') === 'debit') {
            if ($user->wallet_balance < $amount) {
                return back()->withErrors(['amount' => 'Insufficient wallet balance to debit.']);
            }
            $user->wallet_balance -= $amount;
            $user->total_withdrawn += $amount;
            $transactionType = 'withdrawal';
        } else {
            $user->wallet_balance += $amount;
            $user->total_commission += $amount;
            $transactionType = 'incentive';
        }

        DB::transaction(function () use ($user, $amount, $transactionType, $request) {
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => $transactionType,
                'amount' => $amount,
                'status' => 'completed',
                'description' => $request->input('description'),
            ]);
        });

        $this->logAction('Adjust User Balance', "User ID: {$user->id}, Type: {$request->input('type')}, Amount: {$amount}, Description: {$request->input('description')}");

        return back()->with('success', 'User balance updated successfully.');
    }

    /**
     * Display list of orders.
     */
    public function orders(Request $request)
    {
        $status = $request->input('status', 'all');
        $query = Order::with(['user', 'plan']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.orders', compact('orders', 'status'));
    }

    /**
     * Approve manual/pending orders.
     */
    public function approveOrder($id)
    {
        $order = Order::with(['user', 'plan'])->findOrFail($id);

        if ($order->status !== 'pending') {
            return back()->withErrors(['error' => 'Order is already processed.']);
        }

        DB::beginTransaction();
        try {
            $commissionRate = (double) Setting::getValue('commission_percentage', '3.8');
            $userCommission = $order->amount * ($commissionRate / 100);

            // Activate order
            $order->status = 'active';
            $order->commission_earned = $userCommission;
            $order->completed_at = now();
            $order->save();

            // Update user metrics (Principal + Commission added to wallet balance)
            $user = $order->user;
            $user->total_investment += $order->amount;
            $user->wallet_balance += $order->amount + $userCommission;
            $user->total_commission += $userCommission;
            $user->save();

            // Log purchase transaction
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'investment',
                'amount' => $order->amount,
                'status' => 'completed',
                'description' => "Plan Purchased: " . $order->plan->name,
            ]);

            // Log commission transaction
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'commission_credit',
                'amount' => $userCommission,
                'status' => 'completed',
                'description' => "Commission Received (From: " . $order->plan->name . ")",
            ]);

            // Process referral bonus (10% to upline parent)
            if ($user->referred_by) {
                $referrer = User::find($user->referred_by);
                if ($referrer) {
                    $refPercent = (double) Setting::getValue('referral_commission_percentage', '10.0');
                    $refCommission = $order->amount * ($refPercent / 100.0);
                    $referrer->wallet_balance += $refCommission;
                    $referrer->total_commission += $refCommission;
                    $referrer->save();

                    Transaction::create([
                        'user_id' => $referrer->id,
                        'type' => 'commission_credit',
                        'amount' => $refCommission,
                        'status' => 'completed',
                        'description' => "Referral commission from " . ($user->name ?? $user->mobile) . " on " . $order->plan->name,
                    ]);
                }
            }

            DB::commit();

            $this->logAction('Approve Order', "Approved Order ID: {$order->id}, Plan: {$order->plan->name}, User ID: {$user->id}");

            return back()->with('success', 'Order approved and plan activated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Manual order approval failed: " . $e->getMessage());
            return back()->withErrors(['error' => 'Approval failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel manual/pending orders.
     */
    public function cancelOrder($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') {
            return back()->withErrors(['error' => 'Order is already processed.']);
        }

        $order->status = 'cancelled';
        $order->save();

        $this->logAction('Cancel Order', "Cancelled Order ID: {$order->id}");

        return back()->with('success', 'Order cancelled successfully.');
    }

    /**
     * List investment plans.
     */
    public function plans()
    {
        $plans = Plan::orderBy('amount', 'asc')->get();
        return view('admin.plans', compact('plans'));
    }

    /**
     * Create a plan.
     */
    public function storePlan(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'category' => 'required|in:Top Picks,Starter,Silver,Gold,VIP',
        ]);

        $plan = Plan::create([
            'name' => $request->input('name'),
            'amount' => $request->input('amount'),
            'category' => $request->input('category'),
            'daily_commission' => 0.00,
            'duration_days' => 0,
            'status' => 'active',
        ]);

        $this->logAction('Create Plan', "Created Plan Name: {$plan->name}, Amount: {$plan->amount}");

        return back()->with('success', 'Investment plan created successfully.');
    }

    /**
     * Toggle investment plan status (Active/Inactive).
     */
    public function togglePlan($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->status = ($plan->status === 'active') ? 'inactive' : 'active';
        $plan->save();

        $this->logAction('Toggle Plan', "Toggled Plan ID: {$plan->id}, Name: {$plan->name}, Status: {$plan->status}");

        return back()->with('success', 'Plan status updated successfully.');
    }

    /**
     * List global notices.
     */
    public function notifications()
    {
        $notifications = Notification::orderBy('created_at', 'desc')->get();
        return view('admin.notifications', compact('notifications'));
    }

    /**
     * Create broadcast notification.
     */
    public function storeNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        Notification::create($request->only('title', 'content'));

        $this->logAction('Broadcast Announcement', "Broadcasted Announcement: {$request->input('title')}");

        return back()->with('success', 'Announcement broadcasted successfully.');
    }

    /**
     * Display configuration / settings page.
     */
    public function settings()
    {
        $adminId = auth()->id() ?: 1;
        $paytmToken = \Illuminate\Support\Facades\DB::table('paytm_tokens')->where('user_id', $adminId)->first();

        $settings = [
            'active_gateway' => Setting::getValue('active_gateway', 'manual_qr'),
            'paytm_mid' => $paytmToken ? $paytmToken->mid : '',
            'upi_id' => Setting::getValue('upi_id', ''),
            'upi_name' => Setting::getValue('upi_name', ''),
            
            // New configurations
            'website_name' => Setting::getValue('website_name', 'Chetak Pay'),
            'website_logo' => Setting::getValue('website_logo', ''),
            'support_contact' => Setting::getValue('support_contact', 'support@chetakpay.com'),
            'otp_provider' => Setting::getValue('otp_provider', 'fast2sms'),
            'fast2sms_api_url' => Setting::getValue('fast2sms_api_url', 'https://www.fast2sms.com/dev/bulkV2'),
            'fast2sms_api_key' => Setting::getValue('fast2sms_api_key', 'DEFAULT_OTP_API_KEY'),
            'fast2sms_template_id' => Setting::getValue('fast2sms_template_id', '194943'),
            'otpwala_api_url' => Setting::getValue('otpwala_api_url', 'https://sms.otpwala.com/dev/bulkV2'),
            'otpwala_api_key' => Setting::getValue('otpwala_api_key', '3YVsA9uCXvxwloUt9MkkyHpBlDgFzacG6b1iKhP0TW7INL4m8RY4dqlEsH3bvcNa9QgKVhnpeAZwr5xu'),
            'otpwala_template_id' => Setting::getValue('otpwala_template_id', '12294'),
            'otp_route' => Setting::getValue('otp_route', 'dlt'),
            'otp_sender_id' => Setting::getValue('otp_sender_id', 'FOTPSM'),
            'feature_referrals' => Setting::getValue('feature_referrals', '1'),
            'feature_rewards' => Setting::getValue('feature_rewards', '1'),
            'app_version' => Setting::getValue('app_version', '1.0.0'),
            'app_update_url' => Setting::getValue('app_update_url', ''),
            'app_force_update' => Setting::getValue('app_force_update', '0'),
            'maintenance_mode' => Setting::getValue('maintenance_mode', '0'),
            'maintenance_message' => Setting::getValue('maintenance_message', 'System is undergoing scheduled maintenance. Please check back later.'),
            'privacy_policy' => Setting::getValue('privacy_policy', ''),
            'about_us' => Setting::getValue('about_us', ''),
            'terms_conditions' => Setting::getValue('terms_conditions', ''),
            'commission_percentage' => Setting::getValue('commission_percentage', '3.8'),
            'welcome_bonus_amount' => Setting::getValue('welcome_bonus_amount', '50.00'),
            'daily_attendance_bonus_amount' => Setting::getValue('daily_attendance_bonus_amount', '5.00'),
            'referral_commission_percentage' => Setting::getValue('referral_commission_percentage', '10.0'),
        ];

        return view('admin.settings', compact('settings'));
    }

    /**
     * Save configuration / settings.
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'active_gateway' => 'required|in:upi_qr,manual_qr',
            'upi_id' => 'nullable|string',
            'upi_name' => 'nullable|string',
            'paytm_mid' => 'nullable|string',
            
            // New settings validations
            'website_name' => 'required|string|max:255',
            'website_logo' => 'nullable|url',
            'support_contact' => 'required|string',
            'otp_provider' => 'required|in:fast2sms,otpwala',
            'fast2sms_api_url' => 'nullable|url',
            'fast2sms_api_key' => 'nullable|string',
            'fast2sms_template_id' => 'nullable|string',
            'otpwala_api_url' => 'nullable|url',
            'otpwala_api_key' => 'nullable|string',
            'otpwala_template_id' => 'nullable|string',
            'otp_route' => 'nullable|string',
            'otp_sender_id' => 'nullable|string',
            'app_version' => 'required|string',
            'app_update_url' => 'nullable|url',
            'maintenance_message' => 'nullable|string',
            'privacy_policy' => 'nullable|string',
            'about_us' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'commission_percentage' => 'required|numeric|min:0|max:100',
            'welcome_bonus_amount' => 'required|numeric|min:0',
            'daily_attendance_bonus_amount' => 'required|numeric|min:0',
            'referral_commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $keys = [
            'active_gateway',
            'upi_id',
            'upi_name',
            'website_name',
            'website_logo',
            'support_contact',
            'otp_provider',
            'fast2sms_api_url',
            'fast2sms_api_key',
            'fast2sms_template_id',
            'otpwala_api_url',
            'otpwala_api_key',
            'otpwala_template_id',
            'otp_route',
            'otp_sender_id',
            'app_version',
            'app_update_url',
            'maintenance_message',
            'privacy_policy',
            'about_us',
            'terms_conditions',
            'commission_percentage',
            'welcome_bonus_amount',
            'daily_attendance_bonus_amount',
            'referral_commission_percentage',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::setValue($key, $request->input($key));
            }
        }

        // Sync Reward amounts with the newly saved settings
        if ($request->has('welcome_bonus_amount')) {
            \App\Models\Reward::where('category', 'newbie')->update([
                'amount' => $request->input('welcome_bonus_amount')
            ]);
        }
        if ($request->has('daily_attendance_bonus_amount')) {
            \App\Models\Reward::where('category', 'daily')->update([
                'amount' => $request->input('daily_attendance_bonus_amount')
            ]);
        }

        // Handle checkboxes/toggles
        Setting::setValue('feature_referrals', $request->has('feature_referrals') ? '1' : '0');
        Setting::setValue('feature_rewards', $request->has('feature_rewards') ? '1' : '0');
        Setting::setValue('app_force_update', $request->has('app_force_update') ? '1' : '0');
        Setting::setValue('maintenance_mode', $request->has('maintenance_mode') ? '1' : '0');

        if ($request->has('paytm_mid')) {
            $adminId = auth()->id() ?: 1;
            \Illuminate\Support\Facades\DB::table('paytm_tokens')->updateOrInsert(
                ['user_id' => $adminId],
                ['mid' => $request->input('paytm_mid'), 'updated_at' => now()]
            );
        }

        $this->logAction('Update System Settings', "Updated website, gate, bonus, and app settings.");

        return back()->with('success', 'System configurations updated successfully.');
    }

    /**
     * Show admin activity logs.
     */
    public function logs()
    {
        if (auth()->user()->role !== 'super_admin') {
            return back()->withErrors(['error' => 'Super Admin privileges required to view logs.']);
        }

        $logs = AdminLog::with('admin')->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.logs', compact('logs'));
    }

    /**
     * Show admin accounts (role management).
     */
    public function admins()
    {
        if (auth()->user()->role !== 'super_admin') {
            return back()->withErrors(['error' => 'Super Admin privileges required to view admins.']);
        }

        $admins = User::where('is_admin', true)->orderBy('created_at', 'desc')->get();

        return view('admin.admins', compact('admins'));
    }

    /**
     * Store a new administrator account.
     */
    public function storeAdmin(Request $request)
    {
        if (auth()->user()->role !== 'super_admin') {
            return back()->withErrors(['error' => 'Super Admin privileges required to manage admins.']);
        }

        $request->validate([
            'mobile' => 'required|string|unique:users,mobile',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,super_admin',
        ]);

        $admin = User::create([
            'mobile' => $request->input('mobile'),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'is_admin' => true,
            'role' => $request->input('role'),
            'wallet_balance' => 0.00,
            'total_investment' => 0.00,
            'total_commission' => 0.00,
            'total_withdrawn' => 0.00,
        ]);

        $this->logAction('Create Admin Account', "Created Admin Mobile: {$admin->mobile}, Role: {$admin->role}");

        return back()->with('success', 'Admin account created successfully.');
    }

    /**
     * Show logged in admin profile.
     */
    public function profile()
    {
        $admin = auth()->user();
        return view('admin.profile', compact('admin'));
    }

    /**
     * Change logged in admin password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $admin = auth()->user();

        if (!Hash::check($request->input('current_password'), $admin->password)) {
            return back()->withErrors(['current_password' => 'The current password you entered is incorrect.']);
        }

        $admin->password = Hash::make($request->input('new_password'));
        $admin->save();

        $this->logAction('Change Admin Password', "Password changed for Admin Mobile: {$admin->mobile}");

        return back()->with('success', 'Password updated successfully.');
    }
}
