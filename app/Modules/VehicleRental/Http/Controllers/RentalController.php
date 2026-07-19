<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAssignment;

abstract class RentalController
{
    protected function agreement(TenantScopedRequest $request, int $id): RentalAgreement
    {
        return RentalAgreement::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->findOrFail($id);
    }

    protected function assignment(TenantScopedRequest $request, int $id): RentalAssignment
    {
        return RentalAssignment::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->findOrFail($id);
    }
}
