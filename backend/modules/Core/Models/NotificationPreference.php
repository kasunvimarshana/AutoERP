<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'email_enabled',
        'database_enabled',
        'broadcast_enabled',
        'push_enabled',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'database_enabled' => 'boolean',
        'broadcast_enabled' => 'boolean',
        'push_enabled' => 'boolean',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\IAM\Models\User::class);
    }

    /**
     * Check if a specific channel is enabled for this preference.
     */
    public function isChannelEnabled(string $channel): bool
    {
        $field = "{$channel}_enabled";
        return $this->{$field} ?? false;
    }

    /**
     * Get enabled channels for this preference.
     */
    public function getEnabledChannels(): array
    {
        $channels = [];
        
        if ($this->email_enabled) {
            $channels[] = 'mail';
        }
        if ($this->database_enabled) {
            $channels[] = 'database';
        }
        if ($this->broadcast_enabled) {
            $channels[] = 'broadcast';
        }
        if ($this->push_enabled) {
            $channels[] = 'push';
        }

        return $channels;
    }
}
