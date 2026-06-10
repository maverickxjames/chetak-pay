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
}
