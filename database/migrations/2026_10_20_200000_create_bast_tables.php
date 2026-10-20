<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Berita Acara Serah Terima. Nama & jabatan kedua pihak diketik saat membuat BAST
        // (penerima bisa outlet, bukan user di sistem), jadi disimpan sebagai teks.
        Schema::create('bast_documents', function (Blueprint $table) {
            $table->id();
            $table->date('handover_date');
            $table->string('city', 100);
            $table->string('first_party_name');
            $table->string('first_party_position');
            $table->string('second_party_name');
            $table->string('second_party_position');
            $table->string('signed_file')->nullable();
            $table->string('signed_file_original_name')->nullable();
            $table->timestamp('signed_uploaded_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Isi tabel barang disalin saat BAST dibuat, supaya PDF tetap sama walaupun data
        // aset diubah/dihapus belakangan.
        Schema::create('bast_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bast_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type');
            $table->string('detail');
            $table->string('serial_number')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('condition', 50);
            $table->timestamps();
        });

        Schema::table('asset_logs', function (Blueprint $table) {
            $table->string('condition', 50)->nullable()->after('status_after');
        });
    }

    public function down(): void
    {
        Schema::table('asset_logs', function (Blueprint $table) {
            $table->dropColumn('condition');
        });
        Schema::dropIfExists('bast_items');
        Schema::dropIfExists('bast_documents');
    }
};
