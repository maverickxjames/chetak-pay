<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Setting::setValue('commission_percentage', '3.0');

        // Seed master plans
        Plan::create(['name' => 'Starter I', 'amount' => 1000.00, 'daily_commission' => 50.00, 'duration_days' => 30, 'category' => 'Starter', 'status' => 'active']);
        Plan::create(['name' => 'Silver Plus', 'amount' => 5000.00, 'daily_commission' => 280.00, 'duration_days' => 45, 'category' => 'Silver', 'status' => 'active']);
        Plan::create(['name' => 'Inactive Plan', 'amount' => 2000.00, 'daily_commission' => 100.00, 'duration_days' => 30, 'category' => 'Starter', 'status' => 'inactive']);
    }

    /**
     * Test plans retrieval.
     */
    public function test_plans_endpoint_returns_active_plans()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/plans');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data'); // Only active plans
    }

    public function test_order_creation_validation()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'errors' => [
                        'plan_id',
                        'payment_method'
                    ]
                ]
            ]);
    }

    /**
     * Test order creation success.
     */
    public function test_order_creation_success()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John']);
        $plan = Plan::where('name', 'Starter I')->first();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'plan_id' => $plan->id,
                'payment_method' => 'upi_qr'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'order' => [
                        'id',
                        'user_id',
                        'plan_id',
                        'amount',
                        'status',
                        'payment_method',
                    ],
                    'payment_data' => [
                        'order_id',
                        'amount',
                        'currency',
                        'paytm',
                        'gpay',
                        'cred',
                        'upi',
                        'qr',
                        'byteTransactionId',
                    ]
                ]
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'payment_method' => 'upi_qr'
        ]);
    }

    /**
     * Test payment verification activates order and updates balance/ledger.
     */
    public function test_payment_verification_success()
    {
        // 1. Seed Paytm Token
        \Illuminate\Support\Facades\DB::table('paytm_tokens')->insert([
            'user_id' => 1,
            'mid' => 'TEST_MID_123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create(['mobile' => '9876543210', 'name' => 'John', 'total_investment' => 0.00]);
        $plan = Plan::where('name', 'Starter I')->first();

        $order = Order::create([
            'id' => 'ORDTEST12345',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->amount,
            'status' => 'pending',
            'payment_method' => 'upi_qr',
            'byteTransactionId' => 'TXN_TEST_123',
        ]);

        // Mock Paytm order status check
        \Illuminate\Support\Facades\Http::fake([
            'https://securegw.paytm.in/order/status*' => \Illuminate\Support\Facades\Http::response([
                'STATUS' => 'TXN_SUCCESS',
                'TXNAMOUNT' => '1000.00',
                'BANKTXNID' => 'BANK_TXN_TEST_123'
            ], 200)
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/verify", [
                'payment_txid' => null
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        // Check user investment and balance updated with 3% commission
        $freshUser = $user->fresh();
        $this->assertEquals(1000.00, $freshUser->total_investment);
        $this->assertEquals(1030.00, $freshUser->wallet_balance);
        $this->assertEquals(30.00, $freshUser->total_commission);

        // Check transaction logged for investment
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'investment',
            'amount' => '1000.00',
            'status' => 'completed'
        ]);

        // Check transaction logged for instant commission
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'commission_credit',
            'amount' => '30.00',
            'status' => 'completed'
        ]);
    }

    /**
     * Test manual QR verification saves UTR but stays pending, and then admin approval credits parent.
     */
    public function test_payment_verification_credits_referral()
    {
        $referrer = User::create([
            'mobile' => '9876543210',
            'name' => 'Referrer',
            'wallet_balance' => 0.00,
            'total_commission' => 0.00
        ]);

        $user = User::create([
            'mobile' => '9876543211',
            'name' => 'Referred User',
            'referred_by' => $referrer->id,
            'total_investment' => 0.00
        ]);

        $plan = Plan::where('name', 'Silver Plus')->first(); // 5000.00

        $order = Order::create([
            'id' => 'ORDTEST999',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->amount,
            'status' => 'pending',
            'payment_method' => 'manual_qr',
        ]);

        // 1. Submit UTR (12-digit) via API. Verify that it saves UTR but remains pending.
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/verify", [
                'payment_txid' => '123456789012'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'id' => 'ORDTEST999',
            'status' => 'pending',
            'payment_txid' => '123456789012'
        ]);

        // Referrer should NOT get commission yet
        $this->assertEquals(0.00, $referrer->fresh()->wallet_balance);

        // 2. Approve order by admin and check that referrer gets commission
        $admin = User::create([
            'mobile' => '9999999999',
            'name' => 'Admin User',
            'is_admin' => true,
        ]);

        // Disable CSRF for admin request
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $adminResponse = $this->actingAs($admin)
            ->post("/admin/orders/{$order->id}/approve");

        $adminResponse->assertRedirect();

        // Referrer should get 10% commission = 500.00
        $referrer = $referrer->fresh();
        $this->assertEquals(500.00, $referrer->wallet_balance);
        $this->assertEquals(500.00, $referrer->total_commission);

        // Referrer transaction ledger
        $this->assertDatabaseHas('transactions', [
            'user_id' => $referrer->id,
            'type' => 'commission_credit',
            'amount' => '500.00',
            'status' => 'completed'
        ]);
    }

    /**
     * Test FCM token update endpoint.
     */
    public function test_fcm_token_update()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/fcm-token', [
                'fcm_token' => 'dummy_fcm_token_string'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals('dummy_fcm_token_string', $user->fresh()->fcm_token);
    }

    /**
     * Test rotation/random selection of UPI IDs.
     */
    public function test_upi_rotation()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John']);
        $plan = Plan::where('name', 'Starter I')->first();

        // Configure multiple UPI IDs in settings for manual QR
        \App\Models\Setting::setValue('manual_upi_ids', 'upi1@okaxis, upi2@okaxis, upi3@okaxis');

        $selectedUpiIds = [];
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/v1/orders', [
                    'plan_id' => $plan->id,
                    'payment_method' => 'manual_qr'
                ]);

            $response->assertStatus(200);
            $selectedUpiIds[] = $response->json('data.order.upi');
        }

        // Check that only configured UPI IDs were chosen
        $this->assertNotEmpty($selectedUpiIds);
        foreach ($selectedUpiIds as $upi) {
            $this->assertContains($upi, ['upi1@okaxis', 'upi2@okaxis', 'upi3@okaxis']);
        }
        // Since we ran it 10 times, we expect at least 2 unique UPI IDs to show random selection (highly probable)
        $uniqueSelected = array_unique($selectedUpiIds);
        $this->assertGreaterThan(1, count($uniqueSelected));
    }

    /**
     * Test the public web checkout page returns 200.
     */
    public function test_public_checkout_page()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John']);
        $plan = Plan::where('name', 'Starter I')->first();

        $order = Order::create([
            'id' => 'ORDWEB123',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->amount,
            'status' => 'pending',
            'payment_method' => 'upi_qr',
            'byteTransactionId' => 'TXNWEB123',
        ]);

        $response = $this->get("/pay/{$order->id}");

        $response->assertStatus(200)
            ->assertSee('ORDWEB123')
            ->assertSee('1000.00')
            ->assertSee('Starter I');
    }

    /**
     * Test public checkout verification for upi_qr Paytm status.
     */
    public function test_public_checkout_verify_upi_qr()
    {
        \Illuminate\Support\Facades\DB::table('paytm_tokens')->insert([
            'user_id' => 1,
            'mid' => 'TEST_MID_123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create(['mobile' => '9876543210', 'name' => 'John', 'total_investment' => 0.00]);
        $plan = Plan::where('name', 'Starter I')->first();

        $order = Order::create([
            'id' => 'ORDWEB555',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->amount,
            'status' => 'pending',
            'payment_method' => 'upi_qr',
            'byteTransactionId' => 'TXNWEB555',
        ]);

        // Mock Paytm order status check
        \Illuminate\Support\Facades\Http::fake([
            'https://securegw.paytm.in/order/status*' => \Illuminate\Support\Facades\Http::response([
                'STATUS' => 'TXN_SUCCESS',
                'TXNAMOUNT' => '1000.00',
                'BANKTXNID' => 'BANK_TXN_WEB_555'
            ], 200)
        ]);

        $response = $this->postJson("/pay/{$order->id}/verify");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify order is activated and user balance is credited
        $this->assertEquals('active', $order->fresh()->status);
        $this->assertEquals(1000.00, $user->fresh()->total_investment);
    }

    /**
     * Test public checkout verification for manual_qr UTR.
     */
    public function test_public_checkout_verify_manual_qr()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John']);
        $plan = Plan::where('name', 'Starter I')->first();

        $order = Order::create([
            'id' => 'ORDWEB777',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->amount,
            'status' => 'pending',
            'payment_method' => 'manual_qr',
        ]);

        $response = $this->postJson("/pay/{$order->id}/verify", [
            'payment_txid' => '987654321012'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals('pending', $order->fresh()->status);
        $this->assertEquals('987654321012', $order->fresh()->payment_txid);
    }

    public function test_order_creation_fails_when_gateway_disabled()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John']);
        $plan = Plan::where('name', 'Starter I')->first();

        // 1. Disable Paytm auto gateway
        \App\Models\Setting::setValue('gateway_upi_qr_enabled', '0');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'plan_id' => $plan->id,
                'payment_method' => 'upi_qr'
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Paytm Auto Gateway is currently disabled.');

        // 2. Disable manual gateway
        \App\Models\Setting::setValue('gateway_manual_qr_enabled', '0');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'plan_id' => $plan->id,
                'payment_method' => 'manual_qr'
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Manual QR Gateway is currently disabled.');
    }
}
