<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetLog extends Model
{
    protected $fillable = [
        'asset_id', 'action', 'from_user_id', 'to_user_id',
        'from_location_id', 'to_location_id',
        'status_before', 'status_after', 'condition', 'notes', 'handled_by',
    ];

    // Kondisi barang saat check-out, dipakai juga sebagai isian awal "Kondisi barang" di BAST.
    public const CONDITIONS = ['Baru', 'Bekas'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}