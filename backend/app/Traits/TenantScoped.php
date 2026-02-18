<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tenant Scoped Trait
 *
 * Automatically scopes all queries to the current tenant
 * ensuring complete tenant isolation.
 */
trait TenantScoped
{
    /**
     * Boot the tenant scoped trait for a model.
     */
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function (Model $model) {
            if (auth()->check() && auth()->user()->tenant_id && ! $model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    /**
     * Get the tenant ID column name
     */
    public function getTenantIdColumn(): string
    {
        return 'tenant_id';
    }
}
