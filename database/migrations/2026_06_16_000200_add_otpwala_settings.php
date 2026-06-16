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
        $legacyApiKey = Setting::getValue('otp_api_key', 'DEFAULT_OTP_API_KEY');

        $defaults = [
            'otp_provider' => 'fast2sms',
            'fast2sms_api_url' => 'https://www.fast2sms.com/dev/bulkV2',
            'fast2sms_api_key' => $legacyApiKey,
            'fast2sms_template_id' => Setting::getValue('otp_template_id', '194943'),
            'otpwala_api_url' => 'https://sms.otpwala.com/dev/bulkV2',
            'otpwala_api_key' => '3YVsA9uCXvxwloUt9MkkyHpBlDgFzacG6b1iKhP0TW7INL4m8RY4dqlEsH3bvcNa9QgKVhnpeAZwr5xu',
            'otpwala_template_id' => '12294',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
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
