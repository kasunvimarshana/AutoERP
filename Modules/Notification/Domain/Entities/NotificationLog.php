<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * NotificationLog entity.
 *
 * Immutable record of every notification dispatch attempt,
 * storing channel, recipient, rendered body, and delivery status.
 */
class NotificationLog extends Model
{
    use HasTenant;

    protected $table = 'notification_logs';

    protected $fillable = [
        'tenant_id',
        'notification_template_id',
        'channel',
        'recipient',
        'subject',
        'body',
        'status',
        'error_message',
        'sent_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at'  => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }
}
