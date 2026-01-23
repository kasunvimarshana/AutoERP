<?php

namespace App\Modules\AuthManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'device_name',
        'last_activity',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if session is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Check if session is still active
     */
    public function isStillActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Terminate the session
     */
    public function terminate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Update last activity timestamp
     */
    public function touch($attribute = null): bool
    {
        if ($attribute === null) {
            $this->update(['last_activity' => now()]);
            return parent::touch();
        }
        return parent::touch($attribute);
    }

    /**
     * Parse device information from user agent
     */
    public static function parseDeviceInfo(string $userAgent): array
    {
        $deviceType = 'desktop';
        $deviceName = 'Unknown Device';

        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            $deviceType = 'mobile';
            if (preg_match('/iPhone/', $userAgent)) {
                $deviceName = 'iPhone';
            } elseif (preg_match('/iPad/', $userAgent)) {
                $deviceName = 'iPad';
            } elseif (preg_match('/Android/', $userAgent)) {
                $deviceName = 'Android Device';
            }
        } elseif (preg_match('/Tablet/', $userAgent)) {
            $deviceType = 'tablet';
        }

        return [
            'device_type' => $deviceType,
            'device_name' => $deviceName,
        ];
    }
}
