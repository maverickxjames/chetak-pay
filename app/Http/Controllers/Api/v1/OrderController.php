<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Get list of active plans.
     */
    public function getPlans()
    {
        $plans = Plan::where('status', 'active')->get();
        return response()->json([
            'success' => true,
            'message' => 'Plans retrieved successfully',
            'data' => $plans
        ]);
    }

    /**
     * Get list of user's orders.
     */
    public function getOrders(Request $request)
    {
        $orders = Order::with('plan')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data' => $orders
        ]);
    }

    /**
     * Create a pending order for a plan.
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method' => 'required|in:razorpay,cashfree,upi_qr,manual_qr',
        ]);

        $user = $request->user();
        $plan = Plan::find($request->plan_id);

        if ($plan->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This plan is currently inactive.',
                'data' => null
            ], 422);
        }

        // Generate unique order ID
        $orderId = 'ORD' . time() . str_pad($user->id, 4, '0', STR_PAD_LEFT);

        $order = Order::create([
            'id' => $orderId,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->amount,
            'status' => 'pending',
            'payment_method' => $request->payment_method,
            'commission_earned' => 0.00,
        ]);

        // Mock payment details for gateway initialization
        $paymentData = [
            'order_id' => $orderId,
            'amount' => $plan->amount,
            'currency' => 'INR',
        ];

        if ($request->payment_method === 'razorpay') {
            $paymentData['razorpay_key_id'] = \App\Models\Setting::getValue('razorpay_key_id', 'rzp_test_key');
            $paymentData['razorpay_order_id'] = 'order_' . substr(md5($orderId), 0, 14);
        } elseif ($request->payment_method === 'cashfree') {
            $paymentData['cashfree_app_id'] = \App\Models\Setting::getValue('cashfree_app_id', 'cf_test_appid');
            $paymentData['payment_session_id'] = 'session_' . substr(md5($orderId), 0, 20);
        } elseif ($request->payment_method === 'upi_qr' || $request->payment_method === 'manual_qr') {
            $upiId = \App\Models\Setting::getValue('upi_id', 'chetakpay@okaxis');
            $name = \App\Models\Setting::getValue('upi_name', 'Chetak Pay Admin');
            $paymentData['upi_string'] = "upi://pay?pa={$upiId}&pn=" . urlencode($name) . "&am={$plan->amount}&tr={$orderId}&cu=INR";
            $paymentData['upi_id'] = $upiId;
            $paymentData['upi_name'] = $name;
        }

        return response()->json([
            'success' => true,
            'message' => 'Order initiated successfully',
            'data' => [
                'order' => $order->load('plan'),
                'payment_data' => $paymentData
            ]
        ]);
    }

    /**
     * Verify payment and activate the order/plan.
     */
    public function verifyPayment(Request $request, $id)
    {
        $request->validate([
            'payment_txid' => 'nullable|string', // Gateway signature or UTR reference
        ]);

        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
                'data' => null
            ], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This order has already been processed.',
                'data' => $order
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate instant commission for the user (default 3%)
            $commissionRate = env('MASTER_COMMISSION_RATE', 3.0);
            $userCommission = $order->amount * ($commissionRate / 100);

            // Update order details
            $order->status = 'active';
            $order->payment_txid = $request->payment_txid ?? 'TXN' . time();
            $order->commission_earned = $userCommission;
            $order->completed_at = now();
            $order->save();

            // 1. Update user total investments, wallet balance, and total commission
            $user = $request->user();
            $user->total_investment = ($user->total_investment ?? 0.00) + $order->amount;
            $user->wallet_balance = ($user->wallet_balance ?? 0.00) + $order->amount + $userCommission;
            $user->total_commission = ($user->total_commission ?? 0.00) + $userCommission;
            $user->save();

            // 2. Log transaction for investment purchase
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'investment',
                'amount' => $order->amount,
                'status' => 'completed',
                'description' => "Plan Purchased: " . $order->plan->name,
            ]);

            // Log transaction for user's instant return commission
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'commission_credit',
                'amount' => $userCommission,
                'status' => 'completed',
                'description' => "Commission Received (From: " . $order->plan->name . ")",
            ]);

            // 3. Process referral commission (10% of investment amount to parent)
            if ($user->referred_by) {
                $referrer = User::find($user->referred_by);
                if ($referrer) {
                    $commission = $order->amount * 0.10;
                    $referrer->wallet_balance = ($referrer->wallet_balance ?? 0.00) + $commission;
                    $referrer->total_commission = ($referrer->total_commission ?? 0.00) + $commission;
                    $referrer->save();

                    Transaction::create([
                        'user_id' => $referrer->id,
                        'type' => 'commission_credit',
                        'amount' => $commission,
                        'status' => 'completed',
                        'description' => "Referral commission from " . ($user->name ?? $user->mobile) . " on " . $order->plan->name,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment verified and investment activated successfully',
                'data' => $order->load('plan')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Payment verification failed for order {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
