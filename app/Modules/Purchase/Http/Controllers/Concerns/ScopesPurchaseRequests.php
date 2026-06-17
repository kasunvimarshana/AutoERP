<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;

trait ScopesPurchaseRequests
{
    private function scope(Builder $query, TenantScopedRequest $request): Builder
    {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }

    /**
     * @param  list<\BackedEnum>  $allowedStatuses
     */
    private function assertAllowedStatus(TenantScopedRequest $request, array $allowedStatuses): void
    {
        if (! $request->filled('status')) {
            return;
        }

        $allowed = array_map(
            static fn (\BackedEnum $status): string => (string) $status->value,
            $allowedStatuses,
        );

        if (! in_array((string) $request->input('status'), $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ['Status is not valid for this purchase document.'],
            ]);
        }
    }
}
