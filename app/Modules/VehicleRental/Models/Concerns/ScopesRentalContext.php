<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopesRentalContext
{
    public function scopeForContext(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query->where($this->qualifyColumn('tenant_id'), $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull($this->qualifyColumn('organization_unit_id'))
            : $query->where($this->qualifyColumn('organization_unit_id'), $organizationUnitId);
    }
}
