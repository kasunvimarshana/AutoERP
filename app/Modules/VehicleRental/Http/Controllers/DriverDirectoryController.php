<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Http\Resources\DriverDirectoryResource;
use Modules\VehicleRental\Services\DriverDirectory;

final class DriverDirectoryController
{
    public function index(AgreementRequest $request, DriverDirectory $drivers): AnonymousResourceCollection
    {
        return DriverDirectoryResource::collection(
            $drivers->list($request->context(), $request->perPage(), $request->validated('search')),
        );
    }
}
