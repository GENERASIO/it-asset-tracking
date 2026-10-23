<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dari enum tetap -> string, supaya aksi baru (mis. 'agent_register') tidak perlu
        // migrasi enum tiap kali. Nilai lama tetap aman.
        Schema::table('asset_logs', function (Blueprint $table) {
            $table->string('action', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('asset_logs', function (Blueprint $table) {
            $table->enum('action', ['check_out', 'check_in', 'transfer', 'status_change'])->change();
        });
    }
};
