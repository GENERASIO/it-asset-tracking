<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_code', 'name', 'category_id', 'brand', 'model', 'serial_number',
        'specification', 'location_id', 'assigned_to', 'status',
        'purchase_date', 'purchase_price', 'warranty_expired_at', 'photo',
        'hostname', 'os_name', 'os_version', 'ram_gb', 'storage_gb', 'mac_address', 'last_seen_at',
        'latitude', 'longitude', 'location_accuracy_m', 'geo_captured_at',
        'inventory_synced_at', 'crack_indicators',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expired_at' => 'date',
        'purchase_price' => 'decimal:2',
        'last_seen_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'geo_captured_at' => 'datetime',
        'inventory_synced_at' => 'datetime',
        'crack_indicators' => 'array',
    ];

    public function software()
    {
        return $this->hasMany(AssetSoftware::class)->orderBy('name');
    }

    public function licenses()
    {
        return $this->hasMany(AssetLicense::class)->orderBy('product')->orderBy('name');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function logs()
    {
        return $this->hasMany(AssetLog::class)->latest();
    }

    public function bastDocuments()
    {
        return $this->belongsToMany(BastDocument::class, 'bast_items')->distinct()->latest('handover_date');
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(MaintenanceLog::class)->latest();
    }

    public function photos()
    {
        return $this->hasMany(AssetPhoto::class)->orderBy('sort_order');
    }

    /**
     * Samakan kolom `photo` (dipakai kartu Kanban, tabel, quick view, label cetak)
     * dengan foto pertama di tabel asset_photos.
     */
    public function syncPrimaryPhoto(): void
    {
        $first = $this->photos()->first();
        $this->update(['photo' => $first?->path]);
    }

    /**
     * Label OS gabungan tanpa duplikasi kata (mis. os_version Windows sudah memuat "Windows").
     */
    public function getOsLabelAttribute(): ?string
    {
        if (! $this->os_name) {
            return null;
        }

        if ($this->os_version && str_contains($this->os_version, $this->os_name)) {
            return $this->os_version;
        }

        return trim($this->os_name.' '.$this->os_version);
    }
}