<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AgentToken extends Model
{
    protected $fillable = ['name', 'token', 'created_by', 'last_used_at', 'revoked_at'];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Buat token baru. Mengembalikan plain-text token (hanya tersedia sekali, saat dibuat).
     */
    public static function generate(string $name, int $createdBy): array
    {
        $plain = Str::random(48);

        $agentToken = static::create([
            'name' => $name,
            'token' => hash('sha256', $plain),
            'created_by' => $createdBy,
        ]);

        return [$agentToken, $plain];
    }

    public static function findActiveByPlainToken(string $plain): ?self
    {
        return static::whereNull('revoked_at')
            ->where('token', hash('sha256', $plain))
            ->first();
    }
}
