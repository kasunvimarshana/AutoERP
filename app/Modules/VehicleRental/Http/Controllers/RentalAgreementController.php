<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalTransitionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalAgreementRequest;
use Modules\VehicleRental\Http\Requests\UpdateRentalAgreementRequest;
use Modules\VehicleRental\Http\Resources\RentalAgreementResource;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Services\RentalAgreementService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalAgreementController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function index(ListRentalRequest $request, RentalAgreementService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW);
        return RentalAgreementResource::collection($service->paginate($request->tenantId(), $request->organizationUnitId(), $request->validated(), $request->perPage()));
    }

    public function store(StoreRentalAgreementRequest $request, RentalAgreementService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_AGREEMENTS);
        return (new RentalAgreementResource($service->create($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->currentUserId())))->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW);
        return new RentalAgreementResource($this->scope(RentalAgreement::query(), $request)->with($service->relations())->findOrFail($agreement));
    }

    public function update(UpdateRentalAgreementRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_AGREEMENTS);
        return new RentalAgreementResource($service->updateDraft($this->scope(RentalAgreement::query(), $request)->findOrFail($agreement), $request->validated(), $request->currentUserId()));
    }

    public function transition(RentalTransitionRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_AGREEMENTS);
        return new RentalAgreementResource($service->transition(
            $this->scope(RentalAgreement::query(), $request)->findOrFail($agreement),
            RentalAgreementStatus::from((string) $request->input('status')),
            $request->currentUserId(), $request->input('reason'),
        ));
    }
}
