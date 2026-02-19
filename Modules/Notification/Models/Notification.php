<?php

declare(strict_types=1);

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Auth\Models\User;
use Modules\Notification\Enums\NotificationPriority;
use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Enums\NotificationType;
use Modules\Tenant\Traits\TenantScoped;

/**
 * Notification Model
 *
 * Represents a notification sent to a user through various channels
 */
class Notification extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'user_id',
        'template_id',
        'type',
        'channel',
        'priority',
        'status',
        'subject',
        'body',
        'data',
        'metadata',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'error_message',
        'retry_count',
        'max_retries',
        'scheduled_at',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'priority' => NotificationPriority::class,
        'status' => NotificationStatus::class,
        'data' => 'array',
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
    ];

    /**
     * Get the user who receives this notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the template used for this notification
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    /**
     * Check if notification has been read
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if notification has been sent
     */
    public function isSent(): bool
    {
        return $this->status === NotificationStatus::SENT ||
               $this->status === NotificationStatus::DELIVERED ||
               $this->status === NotificationStatus::READ;
    }

    /**
     * Check if notification has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === NotificationStatus::FAILED;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'status' => NotificationStatus::READ,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => NotificationStatus::SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark notification as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => NotificationStatus::DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => NotificationStatus::FAILED,
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }
}
