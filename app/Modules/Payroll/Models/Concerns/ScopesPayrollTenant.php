<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopesPayrollTenant
{
    public function scopeForTenant(Builder $query, int $tenantId, ?int $organizationUnitId = null): Builder
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query
            : $query->where(fn (Builder $scope) => $scope
                ->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $organizationUnitId));
    }
}
