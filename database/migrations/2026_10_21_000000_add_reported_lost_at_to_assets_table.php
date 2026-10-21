<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // Saat diisi, aset ditandai HILANG: agent melacak lokasi di mana pun (tidak lagi dibatasi
    // Wi-Fi kantor) sampai ditandai ditemukan lagi.
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->timestamp('reported_lost_at')->nullable()->after('geo_captured_at');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('reported_lost_at');
        });
    }
};
