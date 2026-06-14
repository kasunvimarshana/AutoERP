<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Requests\ReleaseQuantityRequest;

abstract class InventoryQueryController
{
    protected function scope(
        Builder $query,
        InventoryLookupRequest|ReleaseQuantityRequest $request,
    ): Builder {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }

    /**
     * @param  list<string>  $filters
     */
    protected function filters(Builder $query, InventoryLookupRequest $request, array $filters): Builder
    {
        foreach ($filters as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return $query;
    }
}
