<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Modules\VehicleRental\Http\Requests\StoreRentalInspectionRequest;
use Modules\VehicleRental\Http\Resources\RentalInspectionResource;
use Modules\VehicleRental\Services\RentalPickupService;
use Modules\VehicleRental\Services\RentalReturnService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalInspectionController extends RentalController
{
    public function pickup(
        StoreRentalInspectionRequest $request,
        int $agreement,
        int $allocation,
        RentalPickupService $service,
        VehicleRentalAuthorizationService $authorization,
    ): RentalInspectionResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_INSPECTIONS,
        );
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
        VehicleRentalAuthorizationService $authorization,
    ): RentalInspectionResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_INSPECTIONS,
        );
        $model = $this->agreement($request, $agreement);

        return new RentalInspectionResource($service->save(
            $model,
            $this->allocation($model, $allocation),
            $request->toData(),
        ));
    }
}
