<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Modules\Core\Models\TenantOwnedModel;
use LogicException;

final class TenantSubscriptionEventModel extends TenantOwnedModel
{
    public $timestamps = false;
    protected $table = 'tenant_subscription_events';

    protected $fillable = [
        'tenant_id', 'tenant_subscription_id', 'previous_subscription_id', 'event_type', 'reason',
        'actor_id', 'actor_type', 'actor_name', 'actor_email', 'occurred_at',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Tenant history records are immutable.');
        });
        static::deleting(static function (): never {
            throw new LogicException('Tenant history records cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'tenant_subscription_id' => 'integer',
            'previous_subscription_id' => 'integer',
            'actor_id' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }
}
