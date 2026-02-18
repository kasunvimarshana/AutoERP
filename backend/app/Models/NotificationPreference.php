<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'channel',
        'event_type',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function isEnabled(int $userId, string $channel, string $eventType): bool
    {
        $preference = static::where('user_id', $userId)
            ->where('channel', $channel)
            ->where('event_type', $eventType)
            ->first();

        // If no preference exists, default to enabled
        return $preference ? $preference->enabled : true;
    }
}
