<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoftwareEntitlement extends Model
{
    public const TYPES = [
        'perpetual' => 'Permanen (beli putus)',
        'subscription' => 'Langganan',
    ];

    protected $fillable = ['name', 'match', 'license_type', 'quantity', 'expires_at', 'notes'];

    protected $casts = [
        'expires_at' => 'date',
        'quantity' => 'integer',
    ];

    /**
     * Jumlah aset yang memakai lisensi ini secara resmi: punya lisensi (bukan yang terindikasi
     * tidak resmi) yang nama/deskripsinya mengandung kata kunci $match.
     */
    public function usedCount(): int
    {
        return AssetLicense::where('suspected', false)
            ->where(function ($q) {
                $q->where('name', 'like', '%'.$this->match.'%')
                  ->orWhere('description', 'like', '%'.$this->match.'%');
            })
            ->distinct('asset_id')
            ->count('asset_id');
    }
}
