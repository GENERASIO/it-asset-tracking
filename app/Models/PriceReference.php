<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceReference extends Model
{
    protected $fillable = ['match_name', 'purchase_price', 'note'];

    protected $casts = [
        'purchase_price' => 'integer',
    ];

    /**
     * Cari harga acuan untuk sebuah model (nama yang dibaca agent).
     */
    public static function priceFor(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return static::where('match_name', $name)->value('purchase_price');
    }
}
