<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantSubscriptionEventModel extends Model
{
    public $timestamps = false;
    protected $table = 'tenant_subscription_events';
    protected $guarded = [];

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
