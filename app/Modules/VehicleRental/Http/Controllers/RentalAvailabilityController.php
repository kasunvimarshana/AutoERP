<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Vehicle\Http\Resources\VehicleResource;
use Modules\VehicleRental\Http\Requests\VehicleAvailabilityRequest;
use Modules\VehicleRental\Services\RentalAvailabilityService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalAvailabilityController
{
    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function index(VehicleAvailabilityRequest $request, RentalAvailabilityService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW);

        return VehicleResource::collection($service->queryAvailable(
            $request->tenantId(), $request->organizationUnitId(),
            (string) $request->input('start_at'), (string) $request->input('end_at'),
            $request->filled('vehicle_category_id') ? (int) $request->input('vehicle_category_id') : null,
            $request->input('search'),
        )->paginate($request->perPage()));
    }
}
