<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // Nama prosesor yang dilaporkan agent, dipakai untuk memperkirakan umur perangkat.
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('cpu')->nullable()->after('os_version');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('cpu');
        });
    }
};
