<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $table = 'tenants';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'cross_org_transactions' => 'boolean',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function tenantPlan(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\TenantPlan',
            'tenant_plan_id'
        );
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Configuration\\Infrastructure\\Persistence\\Eloquent\\Models\\Currency',
            'currency_id'
        );
    }

    public function settingGroups(): HasMany
    {
        return $this->hasMany(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\TenantSettingGroup',
            'tenant_id'
        );
    }

    public function settings(): HasMany
    {
        return $this->hasMany(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\TenantSetting',
            'tenant_id'
        );
    }

    public function documents(): HasMany
    {
        return $this->hasMany(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\TenantDocument',
            'tenant_id'
        );
    }

    public function domains(): HasMany
    {
        return $this->hasMany(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\TenantDomain',
            'tenant_id'
        );
    }
}
