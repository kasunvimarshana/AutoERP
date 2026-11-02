<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalTransitionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalReservationRequest;
use Modules\VehicleRental\Http\Requests\UpdateRentalReservationRequest;
use Modules\VehicleRental\Http\Resources\RentalReservationResource;
use Modules\VehicleRental\Models\RentalReservation;
use Modules\VehicleRental\Services\RentalReservationService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalReservationController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function index(ListRentalRequest $request, RentalReservationService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW);
        return RentalReservationResource::collection($service->paginate($request->tenantId(), $request->organizationUnitId(), $request->validated(), $request->perPage()));
    }

    public function store(StoreRentalReservationRequest $request, RentalReservationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_RESERVATIONS);
        return (new RentalReservationResource($service->create($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->currentUserId())))->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $reservation, RentalReservationService $service): RentalReservationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW);
        return new RentalReservationResource($this->scope(RentalReservation::query(), $request)->with($service->relations())->findOrFail($reservation));
    }

    public function update(UpdateRentalReservationRequest $request, int $reservation, RentalReservationService $service): RentalReservationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_RESERVATIONS);
        return new RentalReservationResource($service->update(
            $this->scope(RentalReservation::query(), $request)->findOrFail($reservation),
            $request->validated(),
            (int) $request->input('expected_version'),
            $request->currentUserId(),
        ));
    }

    public function transition(RentalTransitionRequest $request, int $reservation, RentalReservationService $service): RentalReservationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_RESERVATIONS);
        return new RentalReservationResource($service->transition(
            $this->scope(RentalReservation::query(), $request)->findOrFail($reservation),
            RentalReservationStatus::from((string) $request->input('status')),
            (int) $request->input('expected_version'),
            $request->currentUserId(), $request->input('reason'),
        ));
    }
}
