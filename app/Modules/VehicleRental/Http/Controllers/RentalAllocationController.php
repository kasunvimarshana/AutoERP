<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\AssignRentalDriverRequest;
use Modules\VehicleRental\Http\Requests\CancelRentalAllocationRequest;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalAllocationRequest;
use Modules\VehicleRental\Http\Resources\RentalAllocationResource;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Services\RentalAllocationService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalAllocationController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function index(ListRentalRequest $request, RentalAllocationService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW);
        return RentalAllocationResource::collection($service->paginate($request->tenantId(), $request->organizationUnitId(), $request->validated(), $request->perPage()));
    }

    public function store(StoreRentalAllocationRequest $request, int $agreement, RentalAllocationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_ALLOCATIONS);
        $agreementModel = $this->scope(RentalAgreement::query(), $request)->findOrFail($agreement);
        return (new RentalAllocationResource($service->create($agreementModel, $request->validated(), $request->currentUserId())))->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $allocation, RentalAllocationService $service): RentalAllocationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW);
        return new RentalAllocationResource($this->scope(RentalVehicleAllocation::query(), $request)->with($service->relations())->findOrFail($allocation));
    }

    public function assignDriver(AssignRentalDriverRequest $request, int $allocation, RentalAllocationService $service): RentalAllocationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_ALLOCATIONS);
        $allocationModel = $this->scope(RentalVehicleAllocation::query(), $request)->findOrFail($allocation);
        $service->assignDriver(
            $allocationModel,
            (int) $request->input('expected_version'),
            $request->validated(),
            $request->currentUserId(),
        );
        return new RentalAllocationResource($allocationModel->refresh()->load($service->relations()));
    }

    public function cancel(CancelRentalAllocationRequest $request, int $allocation, RentalAllocationService $service): RentalAllocationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_ALLOCATIONS);
        $allocationModel = $this->scope(RentalVehicleAllocation::query(), $request)->findOrFail($allocation);

        return new RentalAllocationResource($service->cancel(
            $allocationModel,
            (int) $request->input('expected_version'),
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }
}
