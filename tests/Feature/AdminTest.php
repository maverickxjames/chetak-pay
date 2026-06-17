<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Setting;
use App\Models\AdminLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable CSRF for admin web tests
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Setting::setValue('commission_percentage', '3.0');
    }

    /**
     * Test guest cannot access admin dashboard.
     */
    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/admin/login');
    }

    /**
     * Test normal user cannot access admin dashboard.
     */
    public function test_normal_user_cannot_access_admin_dashboard(): void
    {
        $user = User::create([
            'mobile' => '9876543210',
            'name' => 'Regular User',
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertRedirect('/admin/login');
    }

    /**
     * Test admin login fails with invalid credentials.
     */
    public function test_admin_login_fails_with_invalid_credentials(): void
    {
        User::create([
            'mobile' => '9999999999',
            'name' => 'Admin User',
            'is_admin' => true,
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->post('/admin/login', [
            'mobile' => '9999999999',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('mobile');
    }

    /**
     * Test admin login succeeds with correct credentials.
     */
    public function test_admin_login_succeeds_with_correct_credentials(): void
    {
        User::create([
            'mobile' => '9999999999',
            'name' => 'Admin User',
            'is_admin' => true,
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->post('/admin/login', [
            'mobile' => '9999999999',
            'password' => 'correctpassword',
        ]);

        $response->assertRedirect('/admin/dashboard');
    }

    /**
     * Test admin can manually approve a pending order.
     */
    public function test_admin_can_manually_approve_pending_order(): void
    {
        // 1. Setup Admin
        $admin = User::create([
            'mobile' => '9999999999',
            'name' => 'Admin User',
            'is_admin' => true,
            'role' => 'super_admin',
            'password' => Hash::make('admin1234'),
        ]);

        // 2. Setup Referrer and User
        $referrer = User::create([
            'mobile' => '1111111111',
            'name' => 'Upline Parent',
            'referral_code' => 'PARENT101',
        ]);

        $user = User::create([
            'mobile' => '9876543210',
            'name' => 'Downline User',
            'referred_by' => $referrer->id,
        ]);

        // 3. Setup Plan & Pending Order
        $plan = Plan::create([
            'name' => 'Starter Tier I',
            'amount' => 1000.00,
            'daily_commission' => 0.00,
            'duration_days' => 0,
            'category' => 'Starter',
            'status' => 'active',
        ]);

        $order = Order::create([
            'id' => 'ORDTEST001',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->amount,
            'status' => 'pending',
            'payment_method' => 'manual_qr',
            'payment_txid' => 'UTR123456789',
        ]);

        // 4. Perform Order Approval
        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/approve");

        // 5. Assert redirection and database state
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'active',
            'commission_earned' => 30.00, // 3% of 1000
        ]);

        // User should get 1030.00 (1000 investment + 3% commission) and 1000.00 investment recorded
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'total_investment' => 1000.00,
            'wallet_balance' => 1030.00,
            'total_commission' => 30.00,
        ]);

        // Referrer should get 10% referral commission (100.00)
        $this->assertDatabaseHas('users', [
            'id' => $referrer->id,
            'wallet_balance' => 100.00,
            'total_commission' => 100.00,
        ]);

        // Transactions logged
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'investment',
            'amount' => 1000.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'commission_credit',
            'amount' => 30.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $referrer->id,
            'type' => 'commission_credit',
            'amount' => 100.00,
        ]);

        // Verify admin log created
        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $admin->id,
            'action' => 'Approve Order',
        ]);
    }

    /**
     * Test blocked user check.
     */
    public function test_blocked_user_cannot_access_api_and_tokens_revoked()
    {
        $user = User::create([
            'mobile' => '9876543210',
            'name' => 'Blocked Member',
            'is_blocked' => true
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/v1/profile');

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your account has been blocked by the administrator.');

        // Verify that their tokens have been revoked
        $this->assertCount(0, $user->tokens);
    }

    /**
     * Test custom admin withdrawal.
     */
    public function test_admin_can_withdraw_user_funds_with_custom_amount_and_message()
    {
        $admin = User::create([
            'mobile' => '9999999999',
            'is_admin' => true,
            'role' => 'super_admin',
            'password' => Hash::make('admin1234')
        ]);

        $user = User::create([
            'mobile' => '9876543210',
            'wallet_balance' => 1500.00,
            'total_withdrawn' => 0.00
        ]);

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/admin-withdraw", [
            'amount' => 500.00,
            'message' => 'Requested payout transfer'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user = $user->fresh();
        $this->assertEquals(1000.00, $user->wallet_balance);
        $this->assertEquals(500.00, $user->total_withdrawn);

        // Verify transaction logged
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'withdrawal',
            'amount' => 500.00,
            'description' => 'Admin Withdrawal: Requested payout transfer'
        ]);

        // Verify admin log created
        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $admin->id,
            'action' => 'Admin Withdraw User Funds',
        ]);
    }

    /**
     * Test payment gateway config switching.
     */
    public function test_admin_can_switch_gateway_and_save_credentials()
    {
        $admin = User::create([
            'mobile' => '9999999999',
            'is_admin' => true,
            'role' => 'super_admin',
            'password' => Hash::make('admin1234')
        ]);

        Setting::setValue('active_gateway', 'manual_qr');

        $response = $this->actingAs($admin)->post('/admin/settings', [
            'active_gateway' => 'upi_qr',
            'upi_id' => 'new_upi@okaxis',
            'upi_name' => 'Chetak Merchants',
            'website_name' => 'Chetak Pay',
            'support_contact' => 'support@chetakpay.com',
            'otp_provider' => 'fast2sms',
            'app_version' => '1.0.0',
            'commission_percentage' => '3.0',
            'welcome_bonus_amount' => '50.00',
            'daily_attendance_bonus_amount' => '5.00',
            'referral_commission_percentage' => '10.0',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('upi_qr', Setting::getValue('active_gateway'));
        $this->assertEquals('new_upi@okaxis', Setting::getValue('upi_id'));
        $this->assertEquals('50.00', Setting::getValue('welcome_bonus_amount'));
        $this->assertEquals('5.00', Setting::getValue('daily_attendance_bonus_amount'));
        $this->assertEquals('10.0', Setting::getValue('referral_commission_percentage'));

        // Verify admin log created
        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $admin->id,
            'action' => 'Update System Settings',
        ]);
    }

    /**
     * Test admin logs are restricted to super admins.
     */
    public function test_admin_logs_restricted_to_super_admin()
    {
        $superAdmin = User::create([
            'mobile' => '9999999999',
            'is_admin' => true,
            'role' => 'super_admin',
            'password' => Hash::make('admin1234')
        ]);

        $normalAdmin = User::create([
            'mobile' => '8888888888',
            'is_admin' => true,
            'role' => 'admin',
            'password' => Hash::make('admin1234')
        ]);

        // Super Admin access success
        $response = $this->actingAs($superAdmin)->get('/admin/logs');
        $response->assertStatus(200);

        // Normal Admin redirect back with error
        $response = $this->actingAs($normalAdmin)->get('/admin/logs');
        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    /**
     * Test guest can retrieve website config dynamically via public API.
     */
    public function test_config_retrieval_returns_correct_website_details()
    {
        Setting::setValue('website_name', 'Chetak Test App');
        Setting::setValue('maintenance_mode', '1');

        $response = $this->getJson('/api/v1/config');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.website_name', 'Chetak Test App')
            ->assertJsonPath('data.maintenance_mode', true);
    }

    /**
     * Test super admin can update website configurations.
     */
    public function test_admin_can_save_extended_website_configurations()
    {
        $admin = User::create([
            'mobile' => '9999999999',
            'is_admin' => true,
            'role' => 'super_admin',
            'password' => Hash::make('admin1234')
        ]);

        $response = $this->actingAs($admin)->post('/admin/settings', [
            'active_gateway' => 'manual_qr',
            'website_name' => 'Brand New Pay',
            'website_logo' => 'https://brandnew.com/logo.png',
            'support_contact' => 'help@brandnew.com',
            'otp_provider' => 'fast2sms',
            'app_version' => '1.2.0',
            'maintenance_mode' => '1',
            'feature_referrals' => '1',
            'commission_percentage' => '3.0',
            'welcome_bonus_amount' => '100.00',
            'daily_attendance_bonus_amount' => '10.00',
            'referral_commission_percentage' => '15.0',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('Brand New Pay', Setting::getValue('website_name'));
        $this->assertEquals('https://brandnew.com/logo.png', Setting::getValue('website_logo'));
        $this->assertEquals('1.2.0', Setting::getValue('app_version'));
        $this->assertEquals('1', Setting::getValue('maintenance_mode'));
        $this->assertEquals('100.00', Setting::getValue('welcome_bonus_amount'));
        $this->assertEquals('10.00', Setting::getValue('daily_attendance_bonus_amount'));
        $this->assertEquals('15.0', Setting::getValue('referral_commission_percentage'));
    }

    public function test_user_can_save_payout_details_once()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John']);

        // Mock Razorpay IFSC API call
        \Illuminate\Support\Facades\Http::fake([
            'https://ifsc.razorpay.com/SBIN0001234' => \Illuminate\Support\Facades\Http::response([
                'BANK' => 'State Bank of India',
                'IFSC' => 'SBIN0001234'
            ], 200)
        ]);

        // 1. Save details first time
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/payout-details', [
                'account_number' => '1234567890',
                'ifsc_code' => 'SBIN0001234',
                'account_holder_name' => 'John Doe',
                'upi_id' => 'john@upi'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.upi_id', 'john@upi');

        // 2. Try to save again with different details
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/payout-details', [
                'upi_id' => 'other@upi'
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'UPI ID is already added. Contact support to update it.');
    }

    public function test_config_endpoint_returns_custom_gateway_names()
    {
        Setting::setValue('gateway_upi_qr_name', 'My Custom Paytm');
        Setting::setValue('gateway_manual_qr_name', 'My Custom Manual');

        $response = $this->getJson('/api/v1/config');

        $response->assertStatus(200)
            ->assertJsonPath('data.gateway_upi_qr_name', 'My Custom Paytm')
            ->assertJsonPath('data.gateway_manual_qr_name', 'My Custom Manual');
    }

    public function test_invalid_ifsc_code_returns_error()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John']);

        // Mock Razorpay IFSC API call returning 404
        \Illuminate\Support\Facades\Http::fake([
            'https://ifsc.razorpay.com/SBIN0000000' => \Illuminate\Support\Facades\Http::response('Not Found', 404)
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/payout-details', [
                'account_number' => '1234567890',
                'ifsc_code' => 'SBIN0000000',
                'account_holder_name' => 'John Doe'
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid IFSC Code. Please check and try again.');
    }

    public function test_ifsc_not_found_body_returns_error()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John']);

        // Mock Razorpay IFSC API call returning 200 but body is "Not Found"
        \Illuminate\Support\Facades\Http::fake([
            'https://ifsc.razorpay.com/SBIN0000001' => \Illuminate\Support\Facades\Http::response('Not Found', 200)
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/payout-details', [
                'account_number' => '1234567890',
                'ifsc_code' => 'SBIN0000001',
                'account_holder_name' => 'John Doe'
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid IFSC Code. Please check and try again.');
    }
}
