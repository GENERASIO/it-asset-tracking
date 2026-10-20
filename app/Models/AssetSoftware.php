<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetSoftware extends Model
{
    protected $table = 'asset_software';

    protected $fillable = ['asset_id', 'name', 'version', 'publisher', 'installed_on'];

    protected $casts = [
        'installed_on' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
