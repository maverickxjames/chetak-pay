<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Get referral statistics and team members list.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Find team members referred by this user
        $members = User::where('referred_by', $user->id)->get();
        
        $teamMembersCount = $members->count();
        
        // Sum total investment of referred users
        $teamInvestment = $members->sum('total_investment');
        
        $referralLink = 'https://chetakpay.com/register?ref=' . $user->referral_code;

        $membersList = $members->map(function ($member) {
            return [
                'id' => $member->id,
                'name' => $member->name ?: 'New User',
                'mobile' => $member->mobile,
                'total_investment' => number_format($member->total_investment, 2, '.', ''),
                'created_at' => $member->created_at->toIso8601String(),
                'is_active' => $member->total_investment > 0
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Team stats retrieved successfully.',
            'data' => [
                'referral_link' => $referralLink,
                'referral_code' => $user->referral_code,
                'team_members_count' => $teamMembersCount,
                'team_investment' => number_format($teamInvestment, 2, '.', ''),
                'referral_earnings' => number_format($user->total_commission, 2, '.', ''),
                'members' => $membersList
            ]
        ]);
    }
}
