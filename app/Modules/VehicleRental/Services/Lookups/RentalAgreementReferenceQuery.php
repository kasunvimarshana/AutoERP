<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services\Lookups;

use Illuminate\Database\Eloquent\Builder;
use Modules\Tax\Models\TaxGroup;

final class RentalAgreementReferenceQuery
{
    public function activeTaxGroups(int $tenantId, ?int $organizationUnitId): Builder
    {
        return TaxGroup::query()
            ->where('tenant_id', $tenantId)
            ->where('active', true)
            ->when(
                $organizationUnitId === null,
                fn (Builder $query): Builder => $query->whereNull('organization_unit_id'),
                fn (Builder $query): Builder => $query->where(function (Builder $scope) use ($organizationUnitId): void {
                    $scope->whereNull('organization_unit_id')
                        ->orWhere('organization_unit_id', $organizationUnitId);
                }),
            );
    }
}
