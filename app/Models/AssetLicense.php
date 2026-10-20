<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetLicense extends Model
{
    public const STATUS_LABELS = [
        'licensed' => 'Aktif',
        'subscription' => 'Langganan',
        'grace' => 'Masa tenggang',
        'notification' => 'Tidak aktif (mode terbatas)',
        'unlicensed' => 'Tidak berlisensi',
    ];

    protected $fillable = [
        'asset_id', 'product', 'name', 'description', 'channel', 'status',
        'partial_key', 'kms_host', 'expires_at', 'suspected', 'suspected_reason',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'suspected' => 'boolean',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Lisensi resmi yang perlu perhatian IT: tidak aktif / masa tenggang, atau akan habis
     * dalam beberapa hari. Lisensi yang terindikasi tidak resmi sengaja tidak diingatkan.
     */
    public function scopeNeedsAttention($query, int $days)
    {
        return $query->where('suspected', false)
            ->where(function ($q) use ($days) {
                $q->whereIn('status', ['grace', 'notification', 'unlicensed'])
                  ->orWhere(fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<=', now()->addDays($days)));
            });
    }
}
