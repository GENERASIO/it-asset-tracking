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
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expired_at' => 'date',
        'purchase_price' => 'decimal:2',
    ];

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

    public function maintenanceLogs()
    {
        return $this->hasMany(MaintenanceLog::class)->latest();
    }
}