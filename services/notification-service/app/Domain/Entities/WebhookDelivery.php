<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Webhook Delivery Entity
 *
 * Tracks individual webhook delivery attempts and outcomes.
 */
class WebhookDelivery extends Model
{
    protected $table = 'webhook_deliveries';

    protected $fillable = [
        'webhook_subscription_id',
        'event',
        'payload',
        'response_code',
        'response_body',
        'attempts',
        'status',    // pending | delivered | failed
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'delivered_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class);
    }
}
