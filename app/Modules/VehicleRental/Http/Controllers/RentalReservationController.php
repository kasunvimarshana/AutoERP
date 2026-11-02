<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Http\Requests\ConvertRentalReservationRequest;
use Modules\VehicleRental\Http\Requests\RentalReservationTransitionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalReservationRequest;
use Modules\VehicleRental\Http\Requests\UpdateRentalReservationRequest;
use Modules\VehicleRental\Http\Resources\AgreementResource;
use Modules\VehicleRental\Http\Resources\RentalReservationResource;
use Modules\VehicleRental\Models\RentalReservation;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalReservationService;

final class RentalReservationController extends Controller
{
    public function __construct(
        private readonly RentalReservationService $service,
        private readonly RentalAuthorization $auth,
    ) {}

    public function index(StoreRentalReservationRequest $request): AnonymousResourceCollection
    {
        $context = $request->context();
        $this->auth->assertReservation($context, write: false);

        $reservations = $this->service->paginate(
            $context->tenantId,
            $context->organizationUnitId,
            $request->query(),
            (int) $request->query('per_page', 15),
        );

        return RentalReservationResource::collection($reservations);
    }

    public function store(StoreRentalReservationRequest $request): JsonResponse
    {
        $context = $request->context();
        $this->auth->assertReservation($context, write: true);

        $reservation = $this->service->create(
            $request->validated(),
            $context->tenantId,
            $context->organizationUnitId,
            $context->actorId,
        );

        return (new RentalReservationResource($reservation))->response()->setStatusCode(201);
    }

    public function show(StoreRentalReservationRequest $request, RentalReservation $reservation): RentalReservationResource
    {
        $context = $request->context();
        $this->auth->assertReservation($context, write: false);

        $reservation = RentalReservation::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with($this->service->relations())
            ->with('history.user')
            ->findOrFail($reservation->getKey());

        return new RentalReservationResource($reservation);
    }

    public function update(UpdateRentalReservationRequest $request, RentalReservation $reservation): RentalReservationResource
    {
        $context = $request->context();
        $this->auth->assertReservation($context, write: true);

        $reservation = RentalReservation::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->findOrFail($reservation->getKey());

        $updated = $this->service->update(
            $reservation,
            $request->validated(),
            $request->expectedVersion(),
            $context->actorId,
        );

        return new RentalReservationResource($updated);
    }

    public function transition(RentalReservationTransitionRequest $request, RentalReservation $reservation): RentalReservationResource
    {
        $context = $request->context();
        $this->auth->assertReservation($context, write: true);

        $reservation = RentalReservation::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->findOrFail($reservation->getKey());

        $updated = $this->service->transition(
            $reservation,
            RentalReservationStatus::from((string) $request->validated('status')),
            $request->expectedVersion(),
            $context->actorId,
            $request->validated('reason'),
        );

        return new RentalReservationResource($updated);
    }

    public function convert(ConvertRentalReservationRequest $request, RentalReservation $reservation): JsonResponse
    {
        $context = $request->context();
        $this->auth->assertReservation($context, write: true);

        $reservation = RentalReservation::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->findOrFail($reservation->getKey());

        $agreement = $this->service->convertToAgreement(
            $reservation,
            $request->validated(),
            $request->expectedVersion(),
            $context,
        );

        return (new AgreementResource($agreement))->response()->setStatusCode(201);
    }
}
