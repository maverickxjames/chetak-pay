<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Get wallet details and transaction history.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'amount' => number_format($tx->amount, 2, '.', ''),
                    'status' => $tx->status,
                    'description' => $tx->description,
                    'created_at' => $tx->created_at->toIso8601String()
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Wallet fetched successfully.',
            'data' => [
                'wallet_balance' => number_format($user->wallet_balance, 2, '.', ''),
                'total_commission' => number_format($user->total_commission, 2, '.', ''),
                'total_withdrawn' => number_format($user->total_withdrawn, 2, '.', ''),
                'transactions' => $transactions
            ]
        ]);
    }

    /**
     * Get statistics dashboard data.
     */
    public function getStatistics(Request $request)
    {
        $user = $request->user();
        
        // In Process Orders (count of pending orders)
        $inProcessOrders = \App\Models\Order::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
            
        // In Process Amount (sum of pending orders)
        $inProcessAmount = \App\Models\Order::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');
            
        // Estimated Income (sum of daily_commission * duration_days for active orders)
        $estimatedIncome = \App\Models\Order::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->get()
            ->sum(function ($order) {
                if ($order->plan) {
                    return $order->plan->daily_commission * $order->plan->duration_days;
                }
                return 0;
            });
            
        $usdtRate = (double) \App\Models\Setting::getValue('usdt_rate', '109');
        $commissionRate = (double) \App\Models\Setting::getValue('commission_percentage', '3.8');
        $sellingStatus = \App\Models\Setting::getValue('selling_status', 'closed'); // 'open' or 'closed'

        return response()->json([
            'success' => true,
            'message' => 'Statistics fetched successfully.',
            'data' => [
                'balance' => number_format($user->wallet_balance, 2, '.', ''),
                'sell' => number_format($user->total_withdrawn, 2, '.', ''),
                'deposit' => number_format($user->total_investment, 2, '.', ''),
                'commission' => number_format($user->total_commission, 2, '.', ''),
                'usdt_rate' => $usdtRate,
                'in_process_amount' => number_format($inProcessAmount, 2, '.', ''),
                'in_process_orders' => $inProcessOrders,
                'commission_rate' => number_format($commissionRate, 2, '.', ''),
                'estimated_income' => number_format($estimatedIncome, 2, '.', ''),
                'selling_status' => $sellingStatus,
                'date' => now()->format('d/m/Y'),
            ]
        ]);
    }
}
