<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Http\Requests\TenantScopedRequest;

trait ScopesVehicleRentalRequests
{
    private function scope(Builder $query, TenantScopedRequest $request): Builder
    {
        $query->where($query->getModel()->qualifyColumn('tenant_id'), $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull($query->getModel()->qualifyColumn('organization_unit_id'))
            : $query->where($query->getModel()->qualifyColumn('organization_unit_id'), $request->organizationUnitId());
    }
}
