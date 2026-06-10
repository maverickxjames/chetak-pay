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
                'payment_method' => 'razorpay'
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
                        'razorpay_key_id',
                        'razorpay_order_id'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'payment_method' => 'razorpay'
        ]);
    }

    /**
     * Test payment verification activates order and updates balance/ledger.
     */
    public function test_payment_verification_success()
    {
        $user = User::create(['mobile' => '9876543210', 'name' => 'John', 'total_investment' => 0.00]);
        $plan = Plan::where('name', 'Starter I')->first();

        $order = Order::create([
            'id' => 'ORDTEST12345',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->amount,
            'status' => 'pending',
            'payment_method' => 'upi_qr',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/verify", [
                'payment_txid' => 'UTR1234567890'
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
     * Test payment verification credits referral commission to parent.
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

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/verify", [
                'payment_txid' => 'UTR777888'
            ]);

        $response->assertStatus(200);

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
}
