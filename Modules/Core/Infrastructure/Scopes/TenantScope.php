<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global tenant scope.
 *
 * Automatically filters all Eloquent queries by the current tenant.
 * This scope is applied globally to every model that uses the HasTenant trait.
 *
 * Tenant resolution order:
 *   1. Request header `X-Tenant-ID`
 *   2. JWT claim `tenant_id`
 *   3. Config fallback (for seeding/testing)
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = $this->resolveTenantIdPublic();

        if ($tenantId !== null) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }

    /**
     * Resolve the current tenant identifier from the request context.
     * Public so that HasTenant trait can use it when auto-assigning tenant_id on create.
     */
    public function resolveTenantIdPublic(): int|string|null
    {
        return $this->resolveTenantId();
    }

    /**
     * Resolve the current tenant identifier from the request context.
     */
    protected function resolveTenantId(): int|string|null
    {
        // 1. From explicit header (service-to-service calls)
        if (app()->runningInConsole() === false && request()->hasHeader('X-Tenant-ID')) {
            return request()->header('X-Tenant-ID');
        }

        // 2. From authenticated user's tenant_id attribute (set by Auth module)
        if (auth()->check() && isset(auth()->user()->tenant_id)) {
            return auth()->user()->tenant_id;
        }

        // 3. Config fallback (test environments, seeders)
        $configured = config('tenancy.current_tenant_id');

        return $configured !== null ? $configured : null;
    }
}
