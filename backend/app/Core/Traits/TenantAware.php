<?php

namespace App\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Tenant Aware Trait
 * 
 * Automatically scopes all queries to the current tenant.
 * This trait should be used on all models that need tenant isolation.
 */
trait TenantAware
{
    /**
     * Boot the tenant aware trait
     *
     * @return void
     */
    protected static function bootTenantAware(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $builder->where(static::getTenantColumn(), auth()->user()->tenant_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $model->{static::getTenantColumn()} = auth()->user()->tenant_id;
            }
        });
    }

    /**
     * Get the tenant column name
     *
     * @return string
     */
    public static function getTenantColumn(): string
    {
        return 'tenant_id';
    }

    /**
     * Scope query to specific tenant
     *
     * @param Builder $query
     * @param int $tenantId
     * @return Builder
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where(static::getTenantColumn(), $tenantId);
    }

    /**
     * Check if the model belongs to the given tenant
     *
     * @param int $tenantId
     * @return bool
     */
    public function belongsToTenant(int $tenantId): bool
    {
        return $this->{static::getTenantColumn()} === $tenantId;
    }
}
