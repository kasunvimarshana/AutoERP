<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;

abstract class VehicleServiceController
{
    protected function job(TenantScopedRequest $request, int $id): VehicleServiceJob
    {
        return VehicleServiceJob::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->findOrFail($id);
    }

    protected function line(VehicleServiceJob $job, int $id): VehicleServiceJobLine
    {
        return $job->lines()->with('job')->findOrFail($id);
    }

    protected function assignment(VehicleServiceJobLine $line, int $id): VehicleServiceLineEmployee
    {
        return $line->employeeAssignments()->findOrFail($id);
    }
}
