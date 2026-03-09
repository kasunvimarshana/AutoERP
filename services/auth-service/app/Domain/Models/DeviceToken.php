<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'device_tokens';

    protected $fillable = [
        'id',
        'user_id',
        'tenant_id',
        'device_id',
        'device_name',
        'device_type',
        'token_id',
        'refresh_token',
        'fcm_token',
        'last_used_at',
        'last_used_ip',
        'user_agent',
        'is_active',
        'expires_at',
        'metadata',
    ];

    protected $hidden = [
        'refresh_token',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
        'is_active'    => 'boolean',
        'metadata'     => 'array',
    ];

    /**
     * User who owns this device token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Tenant context for this device token.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope to active tokens only.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Scope to a specific device.
     */
    public function scopeForDevice(\Illuminate\Database\Eloquent\Builder $query, string $deviceId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Check if the device token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Mark as used (update last_used_at).
     */
    public function markAsUsed(string $ip): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $ip,
        ]);
    }

    /**
     * Deactivate this device token.
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }
}
