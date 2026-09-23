<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Pindahkan foto tunggal yang sudah ada ke tabel baru supaya langsung muncul di carousel.
        DB::table('assets')->whereNotNull('photo')->where('photo', '!=', '')
            ->select('id', 'photo', 'updated_at', 'created_at')
            ->orderBy('id')
            ->get()
            ->each(function ($asset) {
                DB::table('asset_photos')->insert([
                    'asset_id' => $asset->id,
                    'path' => $asset->photo,
                    'sort_order' => 0,
                    'created_at' => $asset->created_at,
                    'updated_at' => $asset->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_photos');
    }
};
