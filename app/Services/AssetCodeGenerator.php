<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Location;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;

class AssetCodeGenerator
{
    /**
     * Contoh hasil: LTP-HO-0001
     *
     * Dibungkus transaksi + lockForUpdate supaya aman dari race condition kalau
     * dua request barengan bikin aset dengan kategori+lokasi yang sama (mis. dua
     * agent self-registration bersamaan). Caller yang langsung menyimpan Asset
     * setelah memanggil ini juga sebaiknya berada di dalam DB::transaction()
     * yang sama supaya lock tetap terjaga sampai proses insert selesai.
     */
    public static function generate(int $categoryId, int $locationId): string
    {
        return DB::transaction(function () use ($categoryId, $locationId) {
            $category = Category::findOrFail($categoryId);
            $location = Location::findOrFail($locationId);

            $prefix = strtoupper($category->code) . '-' . strtoupper($location->code);

            $lastNumber = Asset::where('asset_code', 'like', $prefix . '-%')
                ->lockForUpdate()
                ->selectRaw('MAX(CAST(SUBSTRING_INDEX(asset_code, "-", -1) AS UNSIGNED)) as max_num')
                ->value('max_num') ?? 0;

            $next = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

            return "{$prefix}-{$next}";
        });
    }
}