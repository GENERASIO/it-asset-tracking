<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Aplikasi terpasang per aset, dikirim agent tiap check-in (diganti seluruhnya tiap sync).
        Schema::create('asset_software', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('version', 100)->nullable();
            $table->string('publisher')->nullable();
            $table->date('installed_on')->nullable();
            $table->timestamps();
            $table->index('name');
        });

        // Status lisensi Office/Windows per aset, dari layanan lisensi Windows (SPP).
        Schema::create('asset_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('product', 20); // office | windows
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('channel', 50)->nullable();
            $table->string('status', 30);
            $table->string('partial_key', 10)->nullable();
            $table->string('kms_host')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('suspected')->default(false);
            $table->string('suspected_reason', 500)->nullable();
            $table->timestamps();
        });

        // Lisensi yang dibeli perusahaan (diinput manual), untuk dibandingkan dengan pemakaian.
        Schema::create('software_entitlements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('match');
            $table->string('license_type', 20); // perpetual | subscription
            $table->unsignedInteger('quantity');
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->timestamp('inventory_synced_at')->nullable()->after('geo_captured_at');
            $table->json('crack_indicators')->nullable()->after('inventory_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['inventory_synced_at', 'crack_indicators']);
        });
        Schema::dropIfExists('software_entitlements');
        Schema::dropIfExists('asset_licenses');
        Schema::dropIfExists('asset_software');
    }
};
