<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // STF = Surat Transfer / Mutasi Aset antar lokasi (outlet / head office).
        Schema::create('stf_documents', function (Blueprint $table) {
            $table->id();
            $table->date('transfer_date');
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->constrained('locations');
            $table->string('reason');                 // Alasan Mutasi
            $table->string('sender_name');            // PIC Pengirim (Staff IT)
            $table->string('supervisor_name');        // Atasan
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Isi tabel disalin saat STF dibuat, supaya dokumen tetap sama walau aset diubah.
        Schema::create('stf_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stf_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_no');
            $table->string('description');
            $table->string('to_location_name');       // Outlet Tujuan (snapshot)
            $table->unsignedInteger('quantity')->default(1);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stf_items');
        Schema::dropIfExists('stf_documents');
    }
};
