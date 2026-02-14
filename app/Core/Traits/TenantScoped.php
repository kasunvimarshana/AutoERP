<?php

namespace App\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tenant Scoped Trait
 *
 * Automatically scopes all queries to the current tenant
 * Ensures strict tenant isolation for multi-tenancy
 */
trait TenantScoped
{
    /**
     * Boot the TenantScoped trait
     */
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = self::getCurrentTenantId();

            if ($tenantId) {
                $builder->where(static::getTenantColumn(), $tenantId);
            }
        });

        static::creating(function (Model $model) {
            if (! $model->getAttribute(static::getTenantColumn())) {
                $model->setAttribute(static::getTenantColumn(), self::getCurrentTenantId());
            }
        });
    }

    /**
     * Get the current tenant ID
     */
    protected static function getCurrentTenantId(): ?int
    {
        return auth()->check() ? auth()->user()->tenant_id : null;
    }

    /**
     * Get the tenant column name
     */
    protected static function getTenantColumn(): string
    {
        return 'tenant_id';
    }

    /**
     * Query without tenant scope
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
