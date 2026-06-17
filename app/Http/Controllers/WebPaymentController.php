<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WebPaymentController extends Controller
{
    /**
     * Show the shareable checkout payment page.
     */
    public function showPayPage($order_id)
    {
        $order = Order::with(['user', 'plan'])->where('id', $order_id)->firstOrFail();

        $paymentData = [
            'order_id' => $order->id,
            'amount' => $order->amount,
            'currency' => 'INR',
            'payment_method' => $order->payment_method ?: 'upi_qr',
        ];

        $upiId = $order->upi ?: Setting::getValue('upi_id', 'chetakpay@okaxis');
        $byteTxId = $order->byteTransactionId ?: ('TXN' . time() . rand(1000, 9999));
        $name = Setting::getValue('upi_name', 'Chetak Pay Admin');

        // General UPI string
        $upiString = "upi://pay?pa={$upiId}&pn=" . urlencode($name) . "&am={$order->amount}&cu=INR&tn=" . urlencode("Payment for Order " . $order->id) . ($byteTxId ? "&tid={$byteTxId}&tr={$byteTxId}" : "");
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($upiString) . "&size=200x200";

        $paymentData['upi'] = $upiString;
        $paymentData['qr'] = $qrUrl;
        $paymentData['upi_id'] = $upiId;
        $paymentData['upi_name'] = $name;

        // Define specific app links
        $paymentData['paytm'] = "paytmmp://cash_wallet?pa={$upiId}&pn=" . urlencode($name) . "&am={$order->amount}&cu=INR&tn=" . urlencode("Payment for Order " . $order->id) . ($byteTxId ? "&tid={$byteTxId}&tr={$byteTxId}" : "");
        $paymentData['gpay'] = "tez://upi/pay?pa={$upiId}&pn=" . urlencode($name) . "&am={$order->amount}&cu=INR&tn=" . urlencode("Payment for Order " . $order->id) . ($byteTxId ? "&tid={$byteTxId}&tr={$byteTxId}" : "");
        $paymentData['phonepe'] = "phonepe://pay?pa={$upiId}&pn=" . urlencode($name) . "&am={$order->amount}&cu=INR&tn=" . urlencode("Payment for Order " . $order->id) . ($byteTxId ? "&tid={$byteTxId}&tr={$byteTxId}" : "");
        $paymentData['cred'] = "credpay://upi/pay?pa={$upiId}&pn=" . urlencode($name) . "&am={$order->amount}&cu=INR&tn=" . urlencode("Payment for Order " . $order->id) . ($byteTxId ? "&tid={$byteTxId}&tr={$byteTxId}" : "");

        if ($paymentData['payment_method'] === 'manual_qr') {
            $paymentData['remark'] = $order->id;
        }

        $settings = [
            'website_name' => Setting::getValue('website_name', 'Chetak Pay'),
            'website_logo' => Setting::getValue('website_logo', ''),
            'support_contact' => Setting::getValue('support_contact', 'support@chetakpay.com'),
        ];

        return view('payment_share', compact('order', 'paymentData', 'settings'));
    }

    /**
     * Verify the payment from the public shared checkout page.
     */
    public function verifyPayPage(Request $request, $order_id)
    {
        $order = Order::with(['user', 'plan'])->where('id', $order_id)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => true,
                'message' => 'Payment has already been processed.',
                'data' => $order
            ]);
        }

        $bankTxId = null;

        // 1. Handle UPI QR Paytm API Status check
        if ($order->payment_method === 'upi_qr') {
            $fetchMid = DB::table('paytm_tokens')->first();
            if (!$fetchMid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paytm MID is not configured on the server.'
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
                $response = Http::post("https://securegw.paytm.in/order/status?JsonData=" . urlencode($jsonData));

                if (!$response->ok()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to reach Paytm server. Please try verification again shortly.'
                    ], 422);
                }

                $data = $response->json();

                if (isset($data['STATUS'])) {
                    if ($data['STATUS'] === 'TXN_SUCCESS' && floatval($data['TXNAMOUNT']) === $amount) {
                        $bankTxId = $data['BANKTXNID'] ?? 'PTM' . time();
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Payment status is still pending or failed. Status: ' . ($data['STATUS'] ?? 'PENDING')
                        ], 422);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No transaction status details returned from Paytm.'
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paytm status query failed: ' . $e->getMessage()
                ], 422);
            }
        }

        // 2. Handle Manual QR UTR submission (awaits admin approval)
        if ($order->payment_method === 'manual_qr') {
            $request->validate([
                'payment_txid' => 'required|string',
            ]);

            $utr = trim($request->payment_txid);
            if (empty($utr) || strlen($utr) !== 12 || !is_numeric($utr)) {
                return response()->json([
                    'success' => false,
                    'message' => 'A valid 12-digit transaction UTR is required.'
                ], 422);
            }

            DB::beginTransaction();
            try {
                $order->payment_txid = $utr;
                $order->save();
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Your UTR reference has been submitted. Admin will review and activate your plan.'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save UTR code: ' . $e->getMessage()
                ], 500);
            }
        }

        // 3. For auto-approving gateway (upi_qr), proceed to database transaction to activate
        DB::beginTransaction();
        try {
            // Calculate instant commission for the user (loaded from dynamic database settings)
            $commissionRate = (double) Setting::getValue('commission_percentage', '3.8');
            $userCommission = $order->amount * ($commissionRate / 100);

            // Update order details
            $order->status = 'active';
            $order->payment_txid = $bankTxId;
            $order->commission_earned = $userCommission;
            $order->completed_at = now();
            $order->save();

            // Update order owner (user receiving investment)
            $user = $order->user;
            $user->total_investment = ($user->total_investment ?? 0.00) + $order->amount;
            $user->wallet_balance = ($user->wallet_balance ?? 0.00) + $order->amount + $userCommission;
            $user->total_commission = ($user->total_commission ?? 0.00) + $userCommission;
            $user->save();

            // Log transaction for investment purchase
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

            // Process referral commission (10% of investment amount to parent)
            if ($user->referred_by) {
                $referrer = User::find($user->referred_by);
                if ($referrer) {
                    $refPercent = (double) Setting::getValue('referral_commission_percentage', '10.0');
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
                'message' => 'Payment verified and investment activated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Web payment verification failed for order {$order_id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
