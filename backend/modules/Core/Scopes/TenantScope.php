<?php

namespace Modules\Core\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Core\Services\TenantContext;

/**
 * Global scope for automatic tenant filtering
 * Prevents cross-tenant data leaks at ORM level
 */
class TenantScope implements Scope
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply if model has tenant_id column
        if (!$this->shouldApplyScope($model)) {
            return;
        }

        $tenantId = $this->tenantContext->getTenantId();

        // Only apply if we have a current tenant
        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }

    /**
     * Extend the query builder with methods to bypass the scope
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forAllTenants', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forTenant', function (Builder $builder, int $tenantId) {
            return $builder->withoutGlobalScope($this)
                ->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
        });
    }

    /**
     * Determine if the scope should be applied to the model
     */
    protected function shouldApplyScope(Model $model): bool
    {
        // Check if model has tenant_id in fillable
        return in_array('tenant_id', $model->getFillable());
    }
}
