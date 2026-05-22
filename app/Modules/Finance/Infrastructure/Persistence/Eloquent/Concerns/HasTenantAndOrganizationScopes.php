<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasTenantAndOrganizationScopes
{
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForOrganizationUnit(Builder $query, int $organizationUnitId): Builder
    {
        return $query->where('organization_unit_id', $organizationUnitId);
    }
}
