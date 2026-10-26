<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BastItem extends Model
{
    protected $fillable = [
        'bast_document_id', 'asset_id', 'item_type', 'detail', 'spec', 'serial_number', 'quantity', 'condition',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function document()
    {
        return $this->belongsTo(BastDocument::class, 'bast_document_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
