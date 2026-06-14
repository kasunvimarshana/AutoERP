<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Modules\VehicleRental\Http\Requests\StoreRentalInspectionRequest;
use Modules\VehicleRental\Http\Resources\RentalInspectionResource;
use Modules\VehicleRental\Services\RentalPickupService;
use Modules\VehicleRental\Services\RentalReturnService;

final class RentalInspectionController extends RentalController
{
    public function pickup(
        StoreRentalInspectionRequest $request,
        int $agreement,
        int $allocation,
        RentalPickupService $service,
    ): RentalInspectionResource {
        $model = $this->agreement($request, $agreement);

        return new RentalInspectionResource($service->save(
            $model,
            $this->allocation($model, $allocation),
            $request->toData(),
        ));
    }

    public function return(
        StoreRentalInspectionRequest $request,
        int $agreement,
        int $allocation,
        RentalReturnService $service,
    ): RentalInspectionResource {
        $model = $this->agreement($request, $agreement);

        return new RentalInspectionResource($service->save(
            $model,
            $this->allocation($model, $allocation),
            $request->toData(),
        ));
    }
}
