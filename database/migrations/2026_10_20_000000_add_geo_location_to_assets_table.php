<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('last_seen_at');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('location_accuracy_m')->nullable()->after('longitude');
            $table->timestamp('geo_captured_at')->nullable()->after('location_accuracy_m');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_accuracy_m', 'geo_captured_at']);
        });
    }
};
