<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    protected $fillable = [
        'asset_id', 'issue', 'action_taken', 'technician', 'cost',
        'reported_at', 'resolved_at', 'status', 'created_by',
    ];

    protected $casts = [
        'reported_at' => 'date',
        'resolved_at' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}