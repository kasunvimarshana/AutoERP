<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\CoreModel;

final class TenantPlanRevisionModel extends CoreModel
{
    public const UPDATED_AT = null;

    protected $table = 'tenant_plan_revisions';

    protected $fillable = [
        'tenant_plan_id', 'revision_number', 'features_schema_version', 'features', 'limits_schema_version', 'limits', 'price',
        'currency_id', 'billing_interval', 'effective_at', 'change_note', 'created_by', 'created_at',
    ];

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Tenant plan revisions are immutable. Create a new revision instead.');
        });
        self::deleting(static function (): never {
            throw new LogicException('Tenant plan revisions cannot be deleted because subscriptions retain historical references.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_plan_id' => 'integer',
            'revision_number' => 'integer',
            'features_schema_version' => 'integer',
            'limits_schema_version' => 'integer',
            'features' => 'array',
            'limits' => 'array',
            'currency_id' => 'integer',
            'effective_at' => 'datetime',
        ]);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TenantPlanModel::class, 'tenant_plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscriptionModel::class, 'tenant_plan_revision_id');
    }
}
