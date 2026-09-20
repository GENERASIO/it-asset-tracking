<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Location;
use App\Models\Asset;

class AssetCodeGenerator
{
    // Contoh hasil: LTP-HO-0001
    public static function generate(int $categoryId, int $locationId): string
    {
        $category = Category::findOrFail($categoryId);
        $location = Location::findOrFail($locationId);

        $prefix = strtoupper($category->code) . '-' . strtoupper($location->code);

        $lastNumber = Asset::where('asset_code', 'like', $prefix . '-%')
            ->selectRaw('MAX(CAST(SUBSTRING_INDEX(asset_code, "-", -1) AS UNSIGNED)) as max_num')
            ->value('max_num') ?? 0;

        $next = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$next}";
    }
}