<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Tenant Scope
 * 
 * Global scope that automatically filters queries by tenant_id
 * to ensure strict data isolation in a multi-tenant application.
 * 
 * This scope is applied automatically to all models using the HasTenant trait.
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply scope if user is authenticated and has a tenant_id
        if (auth()->check() && auth()->user()->tenant_id) {
            $builder->where($model->getTable() . '.tenant_id', auth()->user()->tenant_id);
        }
    }

    /**
     * Extend the query builder with the methods needed for this scope.
     *
     * @param Builder $builder
     * @return void
     */
    public function extend(Builder $builder): void
    {
        $this->addWithoutTenant($builder);
        $this->addWithTenant($builder);
    }

    /**
     * Add the withoutTenant extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addWithoutTenant(Builder $builder): void
    {
        $builder->macro('withoutTenant', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the withTenant extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addWithTenant(Builder $builder): void
    {
        $builder->macro('withTenant', function (Builder $builder, int $tenantId) {
            return $builder->withoutGlobalScope($this)
                ->where('tenant_id', $tenantId);
        });
    }
}
