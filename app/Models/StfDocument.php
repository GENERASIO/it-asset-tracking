<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StfDocument extends Model
{
    private const MONTHS = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    protected $fillable = [
        'transfer_date', 'from_location_id', 'to_location_id',
        'reason', 'sender_name', 'supervisor_name', 'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(StfItem::class)->orderBy('id');
    }

    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNumberAttribute(): string
    {
        return sprintf('STF/%s/%04d', $this->created_at?->format('Y') ?? date('Y'), $this->id);
    }

    public function getTransferDateLongAttribute(): string
    {
        $d = $this->transfer_date;

        return $d ? $d->day.' '.self::MONTHS[(int) $d->month].' '.$d->year : '-';
    }
}
