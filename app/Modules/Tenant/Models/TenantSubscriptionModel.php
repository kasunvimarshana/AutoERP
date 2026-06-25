<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;

final class TenantSubscriptionModel extends TenantOwnedModel
{
    public const UPDATED_AT = null;

    protected $table = 'tenant_subscriptions';

    protected $fillable = [
        'tenant_id',
        'revision_number',
        'operation',
        'tenant_plan_revision_id',
        'supersedes_subscription_id',
        'contract_status',
        'starts_at',
        'trial_ends_at',
        'ends_at',
        'change_reason',
        'plan_name',
        'plan_slug',
        'plan_features',
        'plan_limits',
        'price',
        'currency_code',
        'currency_symbol',
        'billing_interval',
        'created_by',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Tenant subscription revisions are immutable. Create a new revision instead.');
        });
        static::deleting(static function (): never {
            throw new LogicException('Tenant subscription revisions cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'revision_number' => 'integer',
            'tenant_plan_revision_id' => 'integer',
            'supersedes_subscription_id' => 'integer',
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'plan_features' => 'array',
            'plan_limits' => 'array',
            'price' => 'decimal:4',
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

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_subscription_id');
    }

    public function subsequentRevisions(): HasMany
    {
        return $this->hasMany(self::class, 'supersedes_subscription_id');
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(TenantCurrentSubscriptionModel::class, 'tenant_subscription_id');
    }
}
