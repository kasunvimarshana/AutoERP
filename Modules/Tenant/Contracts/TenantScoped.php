<?php

declare(strict_types=1);

namespace Modules\Tenant\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Services\TenantContext;

/**
 * TenantScoped Trait
 *
 * Automatically scopes queries to the current tenant
 */
trait TenantScoped
{
    /**
     * Boot the tenant scoped trait
     */
    protected static function bootTenantScoped(): void
    {
        // Automatically add tenant_id on create
        static::creating(function (Model $model) {
            if (! $model->getAttribute('tenant_id') && app()->has(TenantContext::class)) {
                $tenantContext = app(TenantContext::class);
                if ($tenantId = $tenantContext->getCurrentTenantId()) {
                    $model->setAttribute('tenant_id', $tenantId);
                }
            }
        });

        // Automatically scope all queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->has(TenantContext::class)) {
                $tenantContext = app(TenantContext::class);
                if ($tenantId = $tenantContext->getCurrentTenantId()) {
                    $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
                }
            }
        });
    }

    /**
     * Get all records without tenant scope
     */
    public static function withoutTenantScope()
    {
        return static::withoutGlobalScope('tenant');
    }
}
