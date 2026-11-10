<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stf_documents', function (Blueprint $table) {
            $table->string('sign_token', 64)->nullable()->unique()->after('id');
            $table->string('sender_signature')->nullable()->after('sender_name');
            $table->timestamp('sender_signed_at')->nullable()->after('sender_signature');
            // PIC Penerima (user di outlet tujuan) mengisi namanya sendiri saat tanda tangan.
            $table->string('receiver_name')->nullable()->after('sender_signed_at');
            $table->string('receiver_signature')->nullable()->after('receiver_name');
            $table->timestamp('receiver_signed_at')->nullable()->after('receiver_signature');
        });

        // Isi token untuk STF yang sudah ada.
        foreach (DB::table('stf_documents')->whereNull('sign_token')->pluck('id') as $id) {
            DB::table('stf_documents')->where('id', $id)->update(['sign_token' => Str::random(48)]);
        }
    }

    public function down(): void
    {
        Schema::table('stf_documents', function (Blueprint $table) {
            $table->dropColumn(['sign_token', 'sender_signature', 'sender_signed_at', 'receiver_name', 'receiver_signature', 'receiver_signed_at']);
        });
    }
};
