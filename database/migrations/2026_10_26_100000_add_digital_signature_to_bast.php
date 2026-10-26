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
        Schema::table('bast_documents', function (Blueprint $table) {
            $table->string('sign_token', 64)->nullable()->unique()->after('id');
            $table->string('first_party_signature')->nullable()->after('first_party_position');
            $table->timestamp('first_party_signed_at')->nullable()->after('first_party_signature');
            $table->string('second_party_signature')->nullable()->after('second_party_position');
            $table->timestamp('second_party_signed_at')->nullable()->after('second_party_signature');
        });

        // Isi token untuk BAST yang sudah ada.
        foreach (DB::table('bast_documents')->whereNull('sign_token')->pluck('id') as $id) {
            DB::table('bast_documents')->where('id', $id)->update(['sign_token' => Str::random(48)]);
        }
    }

    public function down(): void
    {
        Schema::table('bast_documents', function (Blueprint $table) {
            $table->dropColumn(['sign_token', 'first_party_signature', 'first_party_signed_at', 'second_party_signature', 'second_party_signed_at']);
        });
    }
};
