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
            'welcome_bonus_amount' => '50.00',
            'daily_attendance_bonus_amount' => '5.00',
            'referral_commission_percentage' => '10.0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // If the active gateway was razorpay or cashfree, switch to upi_qr
        $activeGateway = Setting::getValue('active_gateway');
        if ($activeGateway === 'razorpay' || $activeGateway === 'cashfree') {
            Setting::setValue('active_gateway', 'upi_qr');
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
