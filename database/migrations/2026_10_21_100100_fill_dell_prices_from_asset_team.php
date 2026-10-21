<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Harga per unit dari tim aset (Oktober 2026).
     * Nilai sekarang 3400/3401/3410 dihitung memakai rasio penyusutan
     * Dell Vostro 3510 (41,67%), karena seangkatan dan tim aset belum punya angkanya.
     *
     * [nama persis di DB => [harga awal, nilai sekarang]]
     */
    private array $prices = [
        'Dell Inc. Vostro 3400'    => [4000000, 2333333],
        'Dell Inc. Vostro 3401'    => [3399000, 1982750],
        'Dell Inc. Latitude 3410'  => [6999000, 4082750],
        'Dell Inc. Vostro 15 3510' => [6660000, 3885000],
        'Dell Inc. Latitude 3420'  => [8214000, 6297400],
    ];

    public function up(): void
    {
        foreach ($this->prices as $name => [$purchase, $current]) {
            DB::table('assets')->where('name', $name)->update([
                'purchase_price' => $purchase,
                'current_value' => $current,
                'purchase_price_estimated' => false,
            ]);
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->prices) as $name) {
            DB::table('assets')->where('name', $name)->update([
                'current_value' => null,
            ]);
        }
    }
};
