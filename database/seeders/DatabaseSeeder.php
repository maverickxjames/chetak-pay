<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Master Investment Plans
        \App\Models\Plan::create(['name' => 'Starter Tier I', 'amount' => 1000.00, 'daily_commission' => 50.00, 'duration_days' => 30, 'category' => 'Starter', 'status' => 'active']);
        \App\Models\Plan::create(['name' => 'Starter Tier II', 'amount' => 2500.00, 'daily_commission' => 130.00, 'duration_days' => 30, 'category' => 'Starter', 'status' => 'active']);
        \App\Models\Plan::create(['name' => 'Silver Plus', 'amount' => 5000.00, 'daily_commission' => 280.00, 'duration_days' => 45, 'category' => 'Silver', 'status' => 'active']);
        \App\Models\Plan::create(['name' => 'Silver Premium', 'amount' => 10000.00, 'daily_commission' => 600.00, 'duration_days' => 45, 'category' => 'Silver', 'status' => 'active']);
        \App\Models\Plan::create(['name' => 'Gold Standard', 'amount' => 25000.00, 'daily_commission' => 1600.00, 'duration_days' => 60, 'category' => 'Gold', 'status' => 'active']);
        \App\Models\Plan::create(['name' => 'Gold Pro', 'amount' => 50000.00, 'daily_commission' => 3500.00, 'duration_days' => 60, 'category' => 'Gold', 'status' => 'active']);
        \App\Models\Plan::create(['name' => 'VIP Elite', 'amount' => 100000.00, 'daily_commission' => 7500.00, 'duration_days' => 90, 'category' => 'VIP', 'status' => 'active']);
        \App\Models\Plan::create(['name' => 'Recommended Special', 'amount' => 15000.00, 'daily_commission' => 950.00, 'duration_days' => 45, 'category' => 'Top Picks', 'status' => 'active']);
        \App\Models\Plan::create(['name' => 'VIP Starter', 'amount' => 50000.00, 'daily_commission' => 3500.00, 'duration_days' => 60, 'category' => 'Top Picks', 'status' => 'active']);

        // Create default rewards
        \App\Models\Reward::create([
            'title' => 'Welcome Bonus',
            'description' => 'Verify your phone number and get started.',
            'amount' => 50.00,
            'category' => 'newbie',
            'required_milestone' => 0
        ]);

        \App\Models\Reward::create([
            'title' => 'Daily Attendance',
            'description' => 'Claim your daily check-in bonus.',
            'amount' => 5.00,
            'category' => 'daily',
            'required_milestone' => 0
        ]);

        \App\Models\Reward::create([
            'title' => 'Recruiter Milestone 1',
            'description' => 'Refer 3 active users to Chetak Pay.',
            'amount' => 200.00,
            'category' => 'team',
            'required_milestone' => 3
        ]);

        \App\Models\Reward::create([
            'title' => 'Recruiter Milestone 2',
            'description' => 'Refer 10 active users to Chetak Pay.',
            'amount' => 1000.00,
            'category' => 'team',
            'required_milestone' => 10
        ]);

        // Create default global notifications
        \App\Models\Notification::create([
            'title' => 'Welcome to Chetak Pay',
            'content' => 'We are excited to have you on board! Explore our investment plans and start earning commissions today.'
        ]);

        \App\Models\Notification::create([
            'title' => 'Double referral commission weekend',
            'content' => 'Invite your friends this Saturday and Sunday to get 2x direct referral bonuses!'
        ]);

        \App\Models\Notification::create([
            'title' => 'VIP plans unlocked',
            'content' => 'High tier VIP investment plans are now available for all registered members.'
        ]);

        // Create admin user
        \App\Models\User::create([
            'mobile' => '9999999999',
            'name' => 'Chetak Admin',
            'email' => 'admin@chetakpay.com',
            'is_admin' => true,
            'role' => 'super_admin',
            'password' => \Illuminate\Support\Facades\Hash::make('admin1234'),
            'referral_code' => 'ADMIN999',
            'wallet_balance' => 0.00,
            'total_investment' => 0.00,
            'total_commission' => 0.00,
            'total_withdrawn' => 0.00,
        ]);

        // Seed default settings for payment gateways
        \App\Models\Setting::create(['key' => 'active_gateway', 'value' => 'manual_qr']);
        \App\Models\Setting::create(['key' => 'razorpay_key_id', 'value' => 'rzp_test_key']);
        \App\Models\Setting::create(['key' => 'razorpay_key_secret', 'value' => 'rzp_test_secret']);
        \App\Models\Setting::create(['key' => 'cashfree_app_id', 'value' => 'cf_test_appid']);
        \App\Models\Setting::create(['key' => 'cashfree_secret_key', 'value' => 'cf_test_secret']);
        \App\Models\Setting::create(['key' => 'upi_id', 'value' => 'chetakpay@okaxis']);
        \App\Models\Setting::create(['key' => 'upi_name', 'value' => 'Chetak Pay Admin']);

        // Create dummy users for testing team and referral statistics
        $parentUser = User::create([
            'mobile' => '9876543210',
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'referral_code' => 'CPABC123',
            'wallet_balance' => 0.00,
            'total_investment' => 5000.00,
            'total_commission' => 250.00,
            'total_withdrawn' => 0.00,
        ]);

        // Add referred users to John Doe
        User::create([
            'mobile' => '9876543211',
            'name' => 'Referred Member 1',
            'email' => 'member1@example.com',
            'referral_code' => 'CPREF001',
            'referred_by' => $parentUser->id,
            'wallet_balance' => 100.00,
            'total_investment' => 1000.00,
            'total_commission' => 50.00,
            'total_withdrawn' => 0.00,
        ]);

        User::create([
            'mobile' => '9876543212',
            'name' => 'Referred Member 2',
            'email' => 'member2@example.com',
            'referral_code' => 'CPREF002',
            'referred_by' => $parentUser->id,
            'wallet_balance' => 50.00,
            'total_investment' => 2500.00,
            'total_commission' => 120.00,
            'total_withdrawn' => 0.00,
        ]);

        User::create([
            'mobile' => '9876543213',
            'name' => 'Referred Member 3',
            'email' => 'member3@example.com',
            'referral_code' => 'CPREF003',
            'referred_by' => $parentUser->id,
            'wallet_balance' => 0.00,
            'total_investment' => 0.00,
            'total_commission' => 0.00,
            'total_withdrawn' => 0.00,
        ]);
    }
}
