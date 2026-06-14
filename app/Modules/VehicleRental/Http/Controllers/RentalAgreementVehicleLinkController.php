<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Http\Requests\RentalActionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalAgreementVehicleLinkRequest;
use Modules\VehicleRental\Http\Resources\RentalAgreementVehicleLinkResource;
use Modules\VehicleRental\Models\RentalAgreementVehicleLink;
use Modules\VehicleRental\Services\RentalAgreementVehicleLinkService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalAgreementVehicleLinkController
{
    public function store(
        StoreRentalAgreementVehicleLinkRequest $request,
        RentalAgreementVehicleLinkService $service,
        VehicleRentalAuthorizationService $authorization,
    ): JsonResponse {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::MANAGE_LINKS,
        );

        return (new RentalAgreementVehicleLinkResource($service->create(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->toData(),
        )))->response()->setStatusCode(201);
    }

    public function cancel(
        RentalActionRequest $request,
        int $link,
        RentalAgreementVehicleLinkService $service,
        VehicleRentalAuthorizationService $authorization,
    ): RentalAgreementVehicleLinkResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::MANAGE_LINKS,
        );
        $model = RentalAgreementVehicleLink::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->findOrFail($link);

        return new RentalAgreementVehicleLinkResource($service->cancel(
            $model,
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }
}
