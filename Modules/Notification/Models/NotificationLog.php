<?php

declare(strict_types=1);

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Enums\NotificationType;
use Modules\Tenant\Traits\TenantScoped;

/**
 * Notification Log Model
 *
 * Logs all notification sending attempts and results
 */
class NotificationLog extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'notification_id',
        'user_id',
        'type',
        'status',
        'channel',
        'recipient',
        'subject',
        'sent_at',
        'delivered_at',
        'failed_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'status' => NotificationStatus::class,
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the notification this log belongs to
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Get the user this log belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
