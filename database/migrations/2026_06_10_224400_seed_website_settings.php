<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            'website_name' => 'Chetak Pay',
            'website_logo' => 'https://pub-9ca94f15ac314332b24f684aabbcd27a.r2.dev/Chetak.png',
            'support_contact' => 'support@chetakpay.com',
            'otp_api_key' => 'ZakGjMyszUGNOucV4rmzOJtR7AdkVTXiBwFQTR2HZtPtSAroZ6MQiqKBCgmx',
            'feature_referrals' => '1',
            'feature_rewards' => '1',
            'app_version' => '1.0.0',
            'app_update_url' => 'https://play.google.com/store/apps/details?id=com.chetakpay.app',
            'app_force_update' => '0',
            'maintenance_mode' => '0',
            'maintenance_message' => 'The system is currently undergoing scheduled maintenance. Please check back soon.',
            'privacy_policy' => 'We value your privacy. We collect your mobile number and email to securely process transactions. We do not sell your personal data to third parties.',
            'about_us' => 'Chetak Pay is a leading secure investment and instant return commission platform.',
            'terms_conditions' => 'By using Chetak Pay, you agree to our terms. Investments carry market risks. Instant returns are credited to your wallet according to the master return rate set by administrators.',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
