<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\ConfirmRentalCustodyEventRequest;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalTransitionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalCustodyEventRequest;
use Modules\VehicleRental\Http\Resources\RentalCustodyEventResource;
use Modules\VehicleRental\Models\RentalCustodyEvent;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Services\RentalCustodyService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalCustodyController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function index(ListRentalRequest $request, RentalCustodyService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW);
        return RentalCustodyEventResource::collection($service->paginate($request->tenantId(), $request->organizationUnitId(), $request->validated(), $request->perPage()));
    }

    public function store(StoreRentalCustodyEventRequest $request, int $allocation, RentalCustodyService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_CUSTODY);
        $allocationModel = $this->scope(RentalVehicleAllocation::query(), $request)->findOrFail($allocation);
        return (new RentalCustodyEventResource($service->create($allocationModel, $request->validated(), $request->currentUserId())))->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $event, RentalCustodyService $service): RentalCustodyEventResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW);
        return new RentalCustodyEventResource($this->scope(RentalCustodyEvent::query(), $request)->with($service->relations())->findOrFail($event));
    }

    public function confirm(ConfirmRentalCustodyEventRequest $request, int $event, RentalCustodyService $service): RentalCustodyEventResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_CUSTODY);
        return new RentalCustodyEventResource($service->confirm(
            $this->scope(RentalCustodyEvent::query(), $request)->findOrFail($event),
            (int) $request->input('expected_version'),
            (int) $request->input('expected_allocation_version'),
            $request->currentUserId(),
        ));
    }

    public function reverse(RentalTransitionRequest $request, int $event, RentalCustodyService $service): RentalCustodyEventResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_CUSTODY);
        return new RentalCustodyEventResource($service->reverse(
            $this->scope(RentalCustodyEvent::query(), $request)->findOrFail($event),
            (int) $request->input('expected_version'),
            $request->currentUserId(), (string) $request->input('reason'),
        ));
    }
}
