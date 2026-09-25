<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Hr\Http\Resources\EmployeeSummaryResource;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Services\DriverDirectory;

final class DriverDirectoryController
{
    public function index(AgreementRequest $request, DriverDirectory $drivers): AnonymousResourceCollection
    {
        return EmployeeSummaryResource::collection(
            $drivers->list($request->context(), $request->perPage(), $request->validated('search')),
        );
    }
}
