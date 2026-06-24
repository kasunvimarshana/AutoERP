<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\TenantOwnedModel;

final class TenantSubscriptionModel extends TenantOwnedModel
{

    protected $table = 'tenant_subscriptions';

    protected $fillable = [
        'tenant_id', 'tenant_plan_revision_id', 'status', 'starts_at', 'trial_ends_at',
        'ends_at', 'cancelled_at', 'cancellation_reason', 'row_version', 'metadata',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'tenant_plan_revision_id' => 'integer',
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(TenantPlanRevisionModel::class, 'tenant_plan_revision_id');
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(TenantCurrentSubscriptionModel::class, 'tenant_subscription_id');
    }
}
