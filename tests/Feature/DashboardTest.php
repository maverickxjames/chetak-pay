<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Reward;
use App\Models\Notification;
use App\Models\UserReward;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Reward $newbieReward;
    private Reward $dailyReward;
    private Reward $teamReward;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard test user
        $this->user = User::create([
            'mobile' => '9876543210',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'referral_code' => 'CPABC123',
            'wallet_balance' => 0.00,
            'total_investment' => 0.00,
            'total_commission' => 0.00,
            'total_withdrawn' => 0.00,
        ]);

        // Create test rewards
        $this->newbieReward = Reward::create([
            'title' => 'Welcome Bonus',
            'description' => 'Verify phone number.',
            'amount' => 50.00,
            'category' => 'newbie',
            'required_milestone' => 0
        ]);

        $this->dailyReward = Reward::create([
            'title' => 'Daily Attendance',
            'description' => 'Daily check-in.',
            'amount' => 5.00,
            'category' => 'daily',
            'required_milestone' => 0
        ]);

        $this->teamReward = Reward::create([
            'title' => 'Milestone Recruiter',
            'description' => 'Recruit 3 members.',
            'amount' => 100.00,
            'category' => 'team',
            'required_milestone' => 3
        ]);
    }

    /**
     * Test GET /api/v1/rewards.
     */
    public function test_rewards_endpoint_returns_status(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/rewards');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rewards retrieved successfully.'
            ]);

        // Newbie is excluded, Daily should be claimable, Team reward should be locked (0 referrals < 3 required)
        $rewardsData = $response->json('data');
        $this->assertCount(2, $rewardsData);
        $this->assertEquals('CLAIMABLE', $rewardsData[0]['status']); // daily
        $this->assertEquals('LOCKED', $rewardsData[1]['status']); // team
    }

    public function test_rewards_claim_newbie_manual_claim_disabled(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/rewards/{$this->newbieReward->id}/claim");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Welcome bonus is automatically credited on registration.'
            ]);
    }

    /**
     * Test claiming same reward twice.
     */
    public function test_cannot_claim_same_reward_twice(): void
    {
        // Pre-claim the daily reward
        UserReward::create([
            'user_id' => $this->user->id,
            'reward_id' => $this->dailyReward->id,
            'claimed_at' => now()
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/rewards/{$this->dailyReward->id}/claim");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Reward already claimed.'
            ]);
    }

    /**
     * Test GET /api/v1/team.
     */
    public function test_team_endpoint_returns_stats(): void
    {
        // Refer 3 users
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'mobile' => "987654321$i",
                'name' => "Member $i",
                'referred_by' => $this->user->id,
                'total_investment' => 1000.00
            ]);
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/team');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'team_members_count' => 3,
                    'team_investment' => '3000.00',
                    'referral_code' => $this->user->referral_code
                ]
            ]);

        $this->assertCount(3, $response->json('data.members'));
    }

    /**
     * Test GET /api/v1/wallet.
     */
    public function test_wallet_endpoint_returns_transactions(): void
    {
        // Add dummy transactions
        Transaction::create([
            'user_id' => $this->user->id,
            'type' => 'commission_credit',
            'amount' => 150.00,
            'description' => 'Referral bonus'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/wallet');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'wallet_balance' => '0.00'
                ]
            ]);

        $this->assertCount(1, $response->json('data.transactions'));
    }

    /**
     * Test GET /api/v1/notifications.
     */
    public function test_notifications_endpoint(): void
    {
        Notification::create([
            'title' => 'Important Update',
            'content' => 'System maintenance.'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test rewards endpoint hides disabled newbie and daily rewards, and claim fails.
     */
    public function test_rewards_respect_toggles(): void
    {
        // 1. Disable Daily Attendance Bonus
        \App\Models\Setting::setValue('daily_attendance_bonus_enabled', '0');

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/rewards');

        $response->assertStatus(200);
        $rewardsData = $response->json('data');
        // Daily reward should NOT be returned (only team is left since newbie is also excluded)
        $this->assertCount(1, $rewardsData);
        $this->assertEquals('team', $rewardsData[0]['category']);

        // Attempting to claim daily reward should fail with 422
        $claimResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/rewards/{$this->dailyReward->id}/claim");
        $claimResponse->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Daily attendance bonus is currently disabled.');

        // Attempting to claim newbie reward always fails
        $claimResponseNewbie = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/rewards/{$this->newbieReward->id}/claim");
        $claimResponseNewbie->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Welcome bonus is automatically credited on registration.');
    }
}
