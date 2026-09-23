<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('hostname')->nullable()->after('specification');
            $table->string('os_name')->nullable()->after('hostname');
            $table->string('os_version')->nullable()->after('os_name');
            $table->unsignedInteger('ram_gb')->nullable()->after('os_version');
            $table->unsignedInteger('storage_gb')->nullable()->after('ram_gb');
            $table->string('mac_address')->nullable()->after('storage_gb');
            $table->timestamp('last_seen_at')->nullable()->after('mac_address');
            $table->index('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['serial_number']);
            $table->dropColumn([
                'hostname', 'os_name', 'os_version', 'ram_gb', 'storage_gb', 'mac_address', 'last_seen_at',
            ]);
        });
    }
};
