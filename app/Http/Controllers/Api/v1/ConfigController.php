<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class ConfigController extends Controller
{
    /**
     * Get system configuration details.
     */
    public function getConfig()
    {
        return response()->json([
            'success' => true,
            'message' => 'Configuration retrieved successfully.',
            'data' => [
                'website_name' => Setting::getValue('website_name', 'Chetak Pay'),
                'website_logo' => Setting::getValue('website_logo', ''),
                'support_contact' => Setting::getValue('support_contact', 'support@chetakpay.com'),
                'feature_referrals' => Setting::getValue('feature_referrals', '1') === '1',
                'feature_rewards' => Setting::getValue('feature_rewards', '1') === '1',
                'app_version' => Setting::getValue('app_version', '1.0.0'),
                'app_update_url' => Setting::getValue('app_update_url', ''),
                'app_force_update' => Setting::getValue('app_force_update', '0') === '1',
                'maintenance_mode' => Setting::getValue('maintenance_mode', '0') === '1',
                'maintenance_message' => Setting::getValue('maintenance_message', 'System is undergoing scheduled maintenance. Please check back later.'),
                'privacy_policy' => Setting::getValue('privacy_policy', ''),
                'about_us' => Setting::getValue('about_us', ''),
                'terms_conditions' => Setting::getValue('terms_conditions', ''),
                'commission_percentage' => (double) Setting::getValue('commission_percentage', '3.8'),
                'default_gateway' => Setting::getValue('active_gateway', 'upi_qr'),
            ]
        ]);
    }
}
