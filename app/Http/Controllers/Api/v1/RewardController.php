<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\UserReward;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RewardController extends Controller
{
    /**
     * Get list of rewards with their claim status for the current user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Count direct referrals
        $referralCount = User::where('referred_by', $user->id)->count();
        
        // Fetch all rewards
        $rewards = Reward::all();
        
        // Get claimed reward IDs
        $claimedRewardIds = UserReward::where('user_id', $user->id)
            ->pluck('reward_id')
            ->toArray();
            
        // Check if daily reward claimed today
        $dailyClaimedToday = UserReward::where('user_id', $user->id)
            ->whereHas('reward', function ($query) {
                $query->where('category', 'daily');
            })
            ->whereDate('claimed_at', Carbon::today())
            ->exists();

        $data = $rewards->map(function ($reward) use ($claimedRewardIds, $referralCount, $dailyClaimedToday) {
            $status = 'LOCKED';
            
            if (in_array($reward->id, $claimedRewardIds)) {
                $status = 'CLAIMED';
            } else {
                switch ($reward->category) {
                    case 'newbie':
                        $status = 'CLAIMABLE';
                        break;
                    case 'daily':
                        $status = $dailyClaimedToday ? 'LOCKED' : 'CLAIMABLE';
                        break;
                    case 'team':
                        $status = ($referralCount >= $reward->required_milestone) ? 'CLAIMABLE' : 'LOCKED';
                        break;
                }
            }

            return [
                'id' => $reward->id,
                'title' => $reward->title,
                'description' => $reward->description,
                'amount' => number_format($reward->amount, 2, '.', ''),
                'category' => $reward->category,
                'required_milestone' => $reward->required_milestone,
                'status' => $status
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Rewards retrieved successfully.',
            'data' => $data
        ]);
    }

    /**
     * Claim a reward.
     */
    public function claim(Request $request, $id)
    {
        $user = $request->user();
        $reward = Reward::find($id);

        if (!$reward) {
            return response()->json([
                'success' => false,
                'message' => 'Reward not found.',
                'data' => (object)[]
            ], 404);
        }

        // Check if already claimed
        $alreadyClaimed = UserReward::where('user_id', $user->id)
            ->where('reward_id', $reward->id)
            ->exists();

        if ($alreadyClaimed) {
            return response()->json([
                'success' => false,
                'message' => 'Reward already claimed.',
                'data' => (object)[]
            ], 422);
        }

        // Check eligibility
        switch ($reward->category) {
            case 'daily':
                $dailyClaimedToday = UserReward::where('user_id', $user->id)
                    ->whereHas('reward', function ($query) {
                        $query->where('category', 'daily');
                    })
                    ->whereDate('claimed_at', Carbon::today())
                    ->exists();
                if ($dailyClaimedToday) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Daily reward already claimed today.',
                        'data' => (object)[]
                    ], 422);
                }
                break;
                
            case 'team':
                $referralCount = User::where('referred_by', $user->id)->count();
                if ($referralCount < $reward->required_milestone) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Referral milestone not reached.',
                        'data' => (object)[]
                    ], 422);
                }
                break;
        }

        // Claim reward transactionally
        DB::transaction(function () use ($user, $reward) {
            UserReward::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'claimed_at' => Carbon::now()
            ]);

            // Add to wallet balance and commission/incentive earnings
            $user->wallet_balance += $reward->amount;
            $user->total_commission += $reward->amount;
            $user->save();

            // Create ledger entry
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'incentive',
                'amount' => $reward->amount,
                'status' => 'completed',
                'description' => 'Claimed reward: ' . $reward->title
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Reward claimed successfully.',
            'data' => [
                'wallet_balance' => number_format($user->wallet_balance, 2, '.', '')
            ]
        ]);
    }
}
