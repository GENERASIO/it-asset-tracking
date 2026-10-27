<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Bersihkan titik GPS yang tersimpan di luar Indonesia (mis. akibat VPN / geolokasi IP salah).
    public function up(): void
    {
        DB::table('assets')
            ->whereNotNull('latitude')
            ->where(function ($q) {
                $q->where('latitude', '<', -11.5)->orWhere('latitude', '>', 6.5)
                    ->orWhere('longitude', '<', 94.5)->orWhere('longitude', '>', 141.5);
            })
            ->update([
                'latitude' => null,
                'longitude' => null,
                'location_accuracy_m' => null,
                'geo_captured_at' => null,
                'location_status' => 'outside_region',
            ]);
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan (data lokasi salah memang dibuang).
    }
};
