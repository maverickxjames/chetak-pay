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
            'otp_api_url' => 'https://www.fast2sms.com/dev/bulkV2',
            'otp_route' => 'dlt',
            'otp_sender_id' => 'FOTPSM',
            'otp_template_id' => '194943',
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
