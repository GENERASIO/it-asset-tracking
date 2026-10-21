<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Nilai sekarang (setelah penyusutan) dari tim aset. Kalau null, laporan pakai
            // perkiraan nilai buku (garis lurus) seperti sebelumnya.
            $table->bigInteger('current_value')->nullable()->after('purchase_price');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('current_value');
        });
    }
};
