<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class TenantCurrentSubscriptionModel extends TenantOwnedModel
{
    public $incrementing = false;
    protected $primaryKey = 'tenant_id';
    protected $table = 'tenant_current_subscriptions';

    protected $fillable = [
        'tenant_id',
        'tenant_subscription_id',
        'state',
        'state_reason',
        'state_changed_at',
        'row_version',
        'assigned_at',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'tenant_subscription_id' => 'integer',
            'state_changed_at' => 'datetime',
            'assigned_at' => 'datetime',
        ]);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscriptionModel::class, 'tenant_subscription_id');
    }
}
