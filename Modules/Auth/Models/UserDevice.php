<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * UserDevice Model
 *
 * Tracks user devices for multi-device authentication
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $user_id
 * @property string $device_id
 * @property string $device_name
 * @property string $device_type
 * @property string $ip_address
 * @property string $user_agent
 * @property array $metadata
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UserDevice extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'device_id',
        'device_name',
        'device_type',
        'ip_address',
        'user_agent',
        'metadata',
        'last_used_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark device as used
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
