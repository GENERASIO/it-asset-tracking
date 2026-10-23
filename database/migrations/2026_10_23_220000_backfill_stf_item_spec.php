<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Isi spesifikasi untuk STF yang sudah dibuat sebelum kolom spec ada,
    // diambil dari data aset terkait (cpu/ram/storage, atau specification).
    public function up(): void
    {
        $items = DB::table('stf_items')->whereNull('spec')->whereNotNull('asset_id')->get();

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

            // Hanya isi dari data hardware terstruktur (cpu/ram/storage), bukan catatan bebas.
            if ($parts) {
                DB::table('stf_items')->where('id', $item->id)->update(['spec' => implode("\n", $parts)]);
            }
        }
    }

    public function down(): void
    {
        // Tidak perlu dikembalikan.
    }
};
