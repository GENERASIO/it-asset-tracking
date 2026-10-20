<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BastDocument extends Model
{
    // Hasil scan BAST yang sudah ditandatangani disimpan di disk privat (berisi tanda tangan),
    // dan hanya bisa diunduh lewat controller oleh staff.
    public const SIGNED_DISK = 'local';

    public const SIGNED_DIR = 'bast';

    private const MONTHS = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    protected $fillable = [
        'handover_date', 'city',
        'first_party_name', 'first_party_position',
        'second_party_name', 'second_party_position',
        'signed_file', 'signed_file_original_name', 'signed_uploaded_at', 'created_by',
    ];

    protected $casts = [
        'handover_date' => 'date',
        'signed_uploaded_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(BastItem::class)->orderBy('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSigned(): bool
    {
        return (bool) $this->signed_file;
    }

    /** "19 Oktober 2026" - locale aplikasi "en", jadi nama bulan ditulis manual. */
    public function getHandoverDateLabelAttribute(): string
    {
        $date = $this->handover_date;

        return $date->day.' '.self::MONTHS[$date->month].' '.$date->year;
    }

    public function getPdfFilenameAttribute(): string
    {
        $codes = $this->items->pluck('asset.asset_code')->filter()->take(3)->implode('_');

        return 'BAST-'.($codes ?: $this->id).'-'.$this->handover_date->format('Ymd').'.pdf';
    }
}
