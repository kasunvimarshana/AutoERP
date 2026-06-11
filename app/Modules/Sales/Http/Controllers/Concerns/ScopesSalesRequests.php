<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Http\Requests\TenantScopedRequest;

trait ScopesSalesRequests
{
    private function scope(Builder $query, TenantScopedRequest $request): Builder
    {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }
}
