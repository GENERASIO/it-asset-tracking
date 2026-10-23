<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StfItem extends Model
{
    protected $fillable = [
        'stf_document_id', 'asset_id', 'asset_no', 'description', 'spec', 'to_location_name', 'quantity', 'notes',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
