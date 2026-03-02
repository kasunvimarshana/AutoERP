<?php

declare(strict_types=1);

namespace Modules\Integration\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * WebhookDelivery entity.
 *
 * Tracks a single outbound webhook dispatch attempt and its outcome.
 */
class WebhookDelivery extends Model
{
    use HasTenant;

    protected $table = 'webhook_deliveries';

    protected $fillable = [
        'tenant_id',
        'webhook_endpoint_id',
        'event_name',
        'payload',
        'status',
        'attempt_count',
        'response_status',
        'response_body',
        'last_attempt_at',
        'next_retry_at',
    ];

    protected $casts = [
        'payload'         => 'array',
        'last_attempt_at' => 'datetime',
        'next_retry_at'   => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
