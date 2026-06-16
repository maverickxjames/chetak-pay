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
            'payment_method' => 'required|in:upi_qr,manual_qr',
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
        $upiId = \App\Models\Setting::getValue('upi_id', 'chetakpay@okaxis');
        $byteTxId = 'TXN' . time() . rand(1000, 9999);

        $order = Order::create([
            'id' => $orderId,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->amount,
            'status' => 'pending',
            'payment_method' => $request->payment_method,
            'commission_earned' => 0.00,
            'upi' => $upiId,
            'byteTransactionId' => $byteTxId,
        ]);

        $paymentData = [
            'order_id' => $orderId,
            'amount' => $plan->amount,
            'currency' => 'INR',
        ];

        if ($request->payment_method === 'upi_qr') {
            $paytm = "paytmmp://cash_wallet?pa={$upiId}&am={$plan->amount}&tn=Payment%20for%20Order%20{$orderId}&tid={$byteTxId}&tr={$byteTxId}&cu=INR&mc=4722&featuretype=money_transfer";
            $gpay = "share://";
            $cred = "credpay://upi/pay?pa={$upiId}&am={$plan->amount}&tn=Payment%20for%20Order%20{$orderId}&tid={$byteTxId}&tr={$byteTxId}&cu=INR&mc=4722&featuretype=money_transfer";
            $upiString = "upi://pay?pa={$upiId}&pn=Merchant&am={$plan->amount}&cu=INR&tn=Payment%20for%20Order%20{$orderId}&tid={$byteTxId}&tr={$byteTxId}&mc=0000&mode=22";
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($upiString) . "&size=200x200";

            $paymentData['paytm'] = $paytm;
            $paymentData['gpay'] = $gpay;
            $paymentData['cred'] = $cred;
            $paymentData['upi'] = $upiString;
            $paymentData['qr'] = $qrUrl;
            $paymentData['byteTransactionId'] = $byteTxId;
        } elseif ($request->payment_method === 'manual_qr') {
            $name = \App\Models\Setting::getValue('upi_name', 'Chetak Pay Admin');
            $paymentData['upi_id'] = $upiId;
            $paymentData['upi_name'] = $name;
            $paymentData['remark'] = $orderId;
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
            'payment_txid' => 'nullable|string', // UTR reference for manual_qr, null for upi_qr
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

        // 1. Handle UPI QR Paytm API Status check
        $bankTxId = null;
        if ($order->payment_method === 'upi_qr') {
            $fetchMid = \Illuminate\Support\Facades\DB::table('paytm_tokens')->first();
            if (!$fetchMid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paytm MID token is not configured on the server.',
                    'data' => null
                ], 422);
            }
            $mid = $fetchMid->mid;
            $byteTransactionId = $order->byteTransactionId;
            $amount = (float) $order->amount;

            $jsonData = json_encode([
                "MID" => $mid,
                "ORDERID" => $byteTransactionId,
            ]);

            try {
                $response = \Illuminate\Support\Facades\Http::post("https://securegw.paytm.in/order/status?JsonData=" . urlencode($jsonData));

                if (!$response->ok()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to reach Paytm server. Please try verification again shortly.',
                        'data' => null
                    ], 422);
                }

                $data = $response->json();

                if (isset($data['STATUS'])) {
                    if ($data['STATUS'] === 'TXN_SUCCESS' && floatval($data['TXNAMOUNT']) === $amount) {
                        $bankTxId = $data['BANKTXNID'] ?? 'PTM' . time();
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Payment status is still pending or failed. Status: ' . ($data['STATUS'] ?? 'PENDING'),
                            'data' => $data
                        ], 422);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No transaction status details returned from Paytm.',
                        'data' => $data
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paytm status query failed: ' . $e->getMessage(),
                    'data' => null
                ], 422);
            }
        }

        // 2. Handle Manual QR UTR submission (awaits admin approval)
        if ($order->payment_method === 'manual_qr') {
            if (empty($request->payment_txid) || strlen($request->payment_txid) !== 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'A valid 12-digit transaction UTR is required for manual checkout.',
                    'data' => null
                ], 422);
            }

            DB::beginTransaction();
            try {
                $order->payment_txid = $request->payment_txid;
                $order->save();
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Your UTR reference has been submitted. Admin will review and activate your plan.',
                    'data' => $order->load('plan')
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save UTR code: ' . $e->getMessage(),
                    'data' => null
                ], 500);
            }
        }

        // 3. For auto-approving gateway (upi_qr), proceed to database transaction to activate
        DB::beginTransaction();
        try {
            // Calculate instant commission for the user (loaded from dynamic database settings)
            $commissionRate = (double) \App\Models\Setting::getValue('commission_percentage', '3.8');
            $userCommission = $order->amount * ($commissionRate / 100);

            // Update order details
            $order->status = 'active';
            $order->payment_txid = $bankTxId;
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
                    $refPercent = (double) \App\Models\Setting::getValue('referral_commission_percentage', '10.0');
                    $commission = $order->amount * ($refPercent / 100.0);
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
