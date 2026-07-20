<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Models\RentalCalculation;
use Modules\VehicleRental\Models\RentalRunningChart;

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

    protected function runningChart(TenantScopedRequest $request, int $id): RentalRunningChart
    {
        return RentalRunningChart::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->findOrFail($id);
    }

    protected function calculation(TenantScopedRequest $request, int $id): RentalCalculation
    {
        return RentalCalculation::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->findOrFail($id);
    }
}
