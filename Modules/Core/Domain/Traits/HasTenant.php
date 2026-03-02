<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Traits;

use Modules\Core\Infrastructure\Scopes\TenantScope;

/**
 * HasTenant trait.
 *
 * Apply this trait to every Eloquent model that belongs to a tenant.
 * It registers the TenantScope globally, ensuring that all queries
 * are automatically filtered by `tenant_id`.
 *
 * CRITICAL: All business table models MUST use this trait.
 * Omitting it is a Critical Violation of the tenant isolation contract.
 */
trait HasTenant
{
    /**
     * Boot the trait.
     */
    public static function bootHasTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (self $model): void {
            if (empty($model->tenant_id)) {
                $tenantId = app(TenantScope::class)->resolveTenantIdPublic();
                if ($tenantId !== null) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    /**
     * Remove the tenant scope for a query (use sparingly — for admin operations only).
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public static function withoutTenantScope(): \Illuminate\Database\Eloquent\Builder
    {
        return static::withoutGlobalScope(TenantScope::class);
    }
}
