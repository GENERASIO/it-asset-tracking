<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BastDocument extends Model
{
    // Hasil scan BAST yang sudah ditandatangani disimpan di disk privat (berisi tanda tangan),
    // dan hanya bisa diunduh lewat controller oleh staff.
    public const SIGNED_DISK = 'local';

    public const SIGNED_DIR = 'bast';

    // Tanda tangan digital (gambar di layar) disimpan di disk privat.
    public const SIGN_DIR = 'bast/signatures';

    private const MONTHS = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    protected $fillable = [
        'handover_date', 'city',
        'first_party_name', 'first_party_position',
        'second_party_name', 'second_party_position',
        'signed_file', 'signed_file_original_name', 'signed_uploaded_at', 'created_by',
        'sign_token', 'first_party_signature', 'first_party_signed_at',
        'second_party_signature', 'second_party_signed_at',
    ];

    protected $casts = [
        'handover_date' => 'date',
        'signed_uploaded_at' => 'datetime',
        'first_party_signed_at' => 'datetime',
        'second_party_signed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $bast) {
            if (empty($bast->sign_token)) {
                $bast->sign_token = \Illuminate\Support\Str::random(48);
            }
        });
    }

    public function isDigitallySigned(): bool
    {
        return (bool) $this->second_party_signed_at;
    }

    public function getSignUrlAttribute(): string
    {
        return route('bast.sign.show', $this->sign_token);
    }

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
