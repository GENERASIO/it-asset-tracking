<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Status lokasi dari check-in terakhir agent: captured / unavailable /
            // skipped_not_office_wifi. Untuk menjelaskan ke IT kenapa map ada/tidak.
            $table->string('location_status', 40)->nullable()->after('geo_captured_at');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('location_status');
        });
    }
};
