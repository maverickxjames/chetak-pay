<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test send-otp validation.
     */
    public function test_send_otp_validation_fails_for_invalid_mobile(): void
    {
        $response = $this->postJson('/api/v1/send-otp', [
            'mobile' => '123' // too short / doesn't match regex
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['errors']
            ]);
    }

    /**
     * Test send-otp success.
     */
    public function test_send_otp_success_creates_otp_record(): void
    {
        $response = $this->postJson('/api/v1/send-otp', [
            'mobile' => '9876543210'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent successfully.'
            ]);

        $this->assertDatabaseHas('otps', [
            'mobile' => '9876543210'
        ]);
    }

    /**
     * Test verify-otp validation & expiration.
     */
    public function test_verify_otp_fails_for_invalid_or_expired_otp(): void
    {
        // 1. Invalid OTP
        $response = $this->postJson('/api/v1/verify-otp', [
            'mobile' => '9876543210',
            'otp' => '000000'
        ]);
        $response->assertStatus(422);

        // 2. Expired OTP
        Otp::create([
            'mobile' => '9876543210',
            'otp' => '123456',
            'expires_at' => Carbon::now()->subMinutes(1),
            'verified' => false
        ]);

        $response = $this->postJson('/api/v1/verify-otp', [
            'mobile' => '9876543210',
            'otp' => '123456'
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test verify-otp success for a new user.
     */
    public function test_verify_otp_success_for_new_user(): void
    {
        Otp::create([
            'mobile' => '9876543210',
            'otp' => '123456',
            'expires_at' => Carbon::now()->addMinutes(10),
            'verified' => false
        ]);

        $response = $this->postJson('/api/v1/verify-otp', [
            'mobile' => '9876543210',
            'otp' => '123456'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP verified successfully.',
                'data' => [
                    'is_new_user' => true
                ]
            ]);

        $this->assertNotNull($response->json('data.token'));
        $this->assertDatabaseHas('users', [
            'mobile' => '9876543210'
        ]);
    }

    /**
     * Test verify-otp success for an existing user.
     */
    public function test_verify_otp_success_for_existing_user(): void
    {
        User::create([
            'mobile' => '9876543210',
            'name' => 'Existing User',
            'email' => 'existing@example.com'
        ]);

        Otp::create([
            'mobile' => '9876543210',
            'otp' => '123456',
            'expires_at' => Carbon::now()->addMinutes(10),
            'verified' => false
        ]);

        $response = $this->postJson('/api/v1/verify-otp', [
            'mobile' => '9876543210',
            'otp' => '123456'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP verified successfully.',
                'data' => [
                    'is_new_user' => false
                ]
            ]);
    }

    /**
     * Test register route requires auth.
     */
    public function test_register_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe'
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test registration success and referral linkage.
     */
    public function test_register_updates_user_and_generates_referral(): void
    {
        // Create referrer
        $referrer = User::create([
            'mobile' => '1111111111',
            'name' => 'Referrer User',
            'referral_code' => 'REF123'
        ]);

        // Create target user
        $user = User::create([
            'mobile' => '9876543210'
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'referral_code' => 'REF123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Registration completed successfully.'
            ]);

        $this->assertDatabaseHas('users', [
            'mobile' => '9876543210',
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'referred_by' => $referrer->id
        ]);

        $updatedUser = User::where('mobile', '9876543210')->first();
        $this->assertNotNull($updatedUser->referral_code);
    }

    /**
     * Test profile retrieval.
     */
    public function test_profile_retrieval(): void
    {
        $user = User::create([
            'mobile' => '9876543210',
            'name' => 'John Doe'
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'mobile' => '9876543210',
                        'name' => 'John Doe'
                    ]
                ]
            ]);
    }

    /**
     * Test logout revokes token.
     */
    public function test_logout_revokes_token(): void
    {
        $user = User::create([
            'mobile' => '9876543210',
            'name' => 'John Doe'
        ]);
 
        $token = $user->createToken('test_token')->plainTextToken;
 
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/logout');
 
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.'
            ]);
 
        $this->assertCount(0, $user->tokens);
    }
 
    /**
     * Test login succeeds with correct credentials.
     */
    public function test_login_succeeds_with_correct_credentials(): void
    {
        User::create([
            'mobile' => '9876543210',
            'name' => 'Test User',
            'password' => \Illuminate\Support\Facades\Hash::make('password123')
        ]);
 
        $response = $this->postJson('/api/v1/login', [
            'mobile' => '9876543210',
            'password' => 'password123'
        ]);
 
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertNotNull($response->json('data.token'));
    }
 
    /**
     * Test login fails for incorrect credentials.
     */
    public function test_login_fails_for_incorrect_credentials(): void
    {
        User::create([
            'mobile' => '9876543210',
            'name' => 'Test User',
            'password' => \Illuminate\Support\Facades\Hash::make('password123')
        ]);
 
        $response = $this->postJson('/api/v1/login', [
            'mobile' => '9876543210',
            'password' => 'wrongpassword'
        ]);
 
        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
 
    /**
     * Test register request dispatches OTP.
     */
    public function test_register_request_succeeds_and_creates_otp_record(): void
    {
        $response = $this->postJson('/api/v1/register/request', [
            'name' => 'Jane Smith',
            'mobile' => '9999988888',
            'password' => 'password123',
            'email' => 'jane@example.com'
        ]);
 
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
 
        $this->assertDatabaseHas('otps', [
            'mobile' => '9999988888'
        ]);
 
        $otp = Otp::where('mobile', '9999988888')->orderBy('created_at', 'desc')->first();
        $this->assertNotNull($otp->payload);
        $payload = json_decode($otp->payload, true);
        $this->assertEquals('Jane Smith', $payload['name']);
    }
 
    /**
     * Test register verification creates user.
     */
    public function test_register_verify_creates_user_and_logs_in(): void
    {
        $payload = json_encode([
            'name' => 'Verification User',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'email' => 'verif@example.com'
        ]);
 
        Otp::create([
            'mobile' => '9999900000',
            'otp' => '654321',
            'expires_at' => Carbon::now()->addMinutes(10),
            'verified' => false,
            'payload' => $payload
        ]);
 
        $response = $this->postJson('/api/v1/register/verify', [
            'mobile' => '9999900000',
            'otp' => '654321'
        ]);
 
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
 
        $this->assertDatabaseHas('users', [
            'mobile' => '9999900000',
            'name' => 'Verification User',
            'email' => 'verif@example.com'
        ]);
    }
 
    /**
     * Test forget password and reset flow.
     */
    public function test_forget_password_and_reset_flow(): void
    {
        $user = User::create([
            'mobile' => '9876543210',
            'name' => 'Test User',
            'password' => \Illuminate\Support\Facades\Hash::make('oldpassword')
        ]);
 
        $response = $this->postJson('/api/v1/forget-password', [
            'mobile' => '9876543210'
        ]);
 
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
 
        $otp = Otp::where('mobile', '9876543210')->orderBy('created_at', 'desc')->first();
        $this->assertNotNull($otp);
 
        $response = $this->postJson('/api/v1/reset-password', [
            'mobile' => '9876543210',
            'otp' => $otp->otp,
            'new_password' => 'newpassword123'
        ]);
 
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
 
        $user = $user->fresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $user->password));
    }
}
