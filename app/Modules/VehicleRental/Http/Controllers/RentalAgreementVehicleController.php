<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalAgreementVehicleRequest;
use Modules\VehicleRental\Http\Resources\RentalAgreementVehicleResource;
use Modules\VehicleRental\Services\RentalAgreementVehicleService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalAgreementVehicleController extends RentalController
{
    public function index(ListRentalRequest $request, int $agreement): AnonymousResourceCollection
    {
        return RentalAgreementVehicleResource::collection(
            $this->agreement($request, $agreement)->vehicles()
                ->with(['vehicle.make', 'vehicle.model', 'pickupInspection.vehicle', 'returnInspection.vehicle'])
                ->get(),
        );
    }

    public function store(
        StoreRentalAgreementVehicleRequest $request,
        int $agreement,
        RentalAgreementVehicleService $service,
        VehicleRentalAuthorizationService $authorization,
    ): JsonResponse {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::MANAGE_ALLOCATIONS,
        );

        return (new RentalAgreementVehicleResource($service->allocate(
            $this->agreement($request, $agreement),
            $request->toData(),
        )))->response()->setStatusCode(201);
    }

    public function replace(
        StoreRentalAgreementVehicleRequest $request,
        int $agreement,
        int $allocation,
        RentalAgreementVehicleService $service,
        VehicleRentalAuthorizationService $authorization,
    ): RentalAgreementVehicleResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::MANAGE_ALLOCATIONS,
        );
        $model = $this->agreement($request, $agreement);

        return new RentalAgreementVehicleResource($service->replace(
            $model,
            $this->allocation($model, $allocation),
            $request->toData(),
        ));
    }
}
