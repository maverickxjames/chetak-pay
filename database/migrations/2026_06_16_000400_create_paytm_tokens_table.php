<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('paytm_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('mid');
            $table->timestamps();
        });

        // Seed a default MID for the primary administrator account (ID = 1)
        \Illuminate\Support\Facades\DB::table('paytm_tokens')->insert([
            'user_id' => 1,
            'mid' => 'MID_DEFAULT_TEST_123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paytm_tokens');
    }
};
