<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stf_items', function (Blueprint $table) {
            // Spesifikasi (Processor, RAM, Storage) ditampilkan di bawah deskripsi.
            $table->text('spec')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('stf_items', function (Blueprint $table) {
            $table->dropColumn('spec');
        });
    }
};
