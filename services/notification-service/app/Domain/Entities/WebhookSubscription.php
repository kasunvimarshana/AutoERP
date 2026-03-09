<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Webhook Subscription Entity
 *
 * Tenant-specific webhook endpoints to receive event notifications.
 */
class WebhookSubscription extends Model
{
    protected $table = 'webhook_subscriptions';

    protected $fillable = [
        'tenant_id',
        'url',
        'events',      // JSON array of subscribed events
        'secret',      // HMAC signing secret
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Check if this subscription handles the given event.
     */
    public function handlesEvent(string $event): bool
    {
        return in_array($event, $this->events ?? [], true)
            || in_array('*', $this->events ?? [], true);
    }
}
