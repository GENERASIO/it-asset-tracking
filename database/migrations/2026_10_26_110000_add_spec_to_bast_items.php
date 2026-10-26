<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bast_items', function (Blueprint $table) {
            $table->text('spec')->nullable()->after('detail');
        });

        // Backfill spesifikasi dari aset terkait (cpu/ram/storage).
        $items = DB::table('bast_items')->whereNull('spec')->whereNotNull('asset_id')->get();
        foreach ($items as $item) {
            $asset = DB::table('assets')->where('id', $item->asset_id)->first();
            if (! $asset) {
                continue;
            }
            $parts = array_filter([
                ! empty($asset->cpu) ? 'Processor: '.$asset->cpu : null,
                ! empty($asset->ram_gb) ? 'RAM: '.$asset->ram_gb.' GB' : null,
                ! empty($asset->storage_gb) ? 'Storage: '.$asset->storage_gb.' GB' : null,
            ]);
            if ($parts) {
                DB::table('bast_items')->where('id', $item->id)->update(['spec' => implode("\n", $parts)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('bast_items', function (Blueprint $table) {
            $table->dropColumn('spec');
        });
    }
};
