<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalActionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalReservationRequest;
use Modules\VehicleRental\Http\Resources\RentalReservationResource;
use Modules\VehicleRental\Http\Resources\RentalStatusHistoryResource;
use Modules\VehicleRental\Services\RentalReservationService;

final class RentalReservationController extends RentalController
{
    public function index(ListRentalRequest $request, RentalReservationService $service): AnonymousResourceCollection
    {
        return RentalReservationResource::collection($service->paginate(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated(),
            $request->perPage(),
        ));
    }

    public function store(StoreRentalReservationRequest $request, RentalReservationService $service): JsonResponse
    {
        return (new RentalReservationResource($service->create($request->toData())))
            ->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $reservation): RentalReservationResource
    {
        return new RentalReservationResource($this->reservation($request, $reservation)
            ->load(['customer', 'supplier', 'vehicle.make', 'vehicle.model']));
    }

    public function update(
        StoreRentalReservationRequest $request,
        int $reservation,
        RentalReservationService $service,
    ): RentalReservationResource {
        return new RentalReservationResource($service->update(
            $this->reservation($request, $reservation),
            $request->toData(),
        ));
    }

    public function pending(
        RentalActionRequest $request,
        int $reservation,
        RentalReservationService $service,
    ): RentalReservationResource {
        return $this->change($request, $reservation, RentalReservationStatus::Pending, $service);
    }

    public function confirm(
        RentalActionRequest $request,
        int $reservation,
        RentalReservationService $service,
    ): RentalReservationResource {
        return $this->change($request, $reservation, RentalReservationStatus::Confirmed, $service);
    }

    public function cancel(
        RentalActionRequest $request,
        int $reservation,
        RentalReservationService $service,
    ): RentalReservationResource {
        return $this->change($request, $reservation, RentalReservationStatus::Cancelled, $service);
    }

    public function history(ListRentalRequest $request, int $reservation): AnonymousResourceCollection
    {
        return RentalStatusHistoryResource::collection(
            $this->reservation($request, $reservation)->statusHistories()->get(),
        );
    }

    private function change(
        RentalActionRequest $request,
        int $reservation,
        RentalReservationStatus $status,
        RentalReservationService $service,
    ): RentalReservationResource {
        return new RentalReservationResource($service->changeStatus(
            $this->reservation($request, $reservation),
            $status,
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }
}
