<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Models\Concerns\HasStatusScope;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

final class TenantModel extends CoreModel
{
    use HasStatusScope;
    use SoftDeletes;

    protected $table = 'tenants';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_plan_id' => 'integer',
            'currency_id' => 'integer',
            'is_active' => 'boolean',
            'cross_org_transactions' => 'boolean',
            'is_isolated' => 'boolean',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'metadata' => 'array',
        ]);
    }

    public function organizationUnits(): HasMany
    {
        return $this->hasMany(OrganizationUnitModel::class, 'tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TenantPlanModel::class, 'tenant_plan_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function settingGroups(): HasMany
    {
        return $this->hasMany(TenantSettingGroupModel::class, 'tenant_id');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(TenantSettingModel::class, 'tenant_id');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomainModel::class, 'tenant_id');
    }
}
