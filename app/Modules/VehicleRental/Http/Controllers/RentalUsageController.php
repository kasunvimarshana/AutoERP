<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Enums\RentalUsageStatus;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalUsageTransitionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalUsageRequest;
use Modules\VehicleRental\Http\Resources\RentalUsageLogResource;
use Modules\VehicleRental\Models\RentalUsageLog;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Services\RentalUsageService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalUsageController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function index(ListRentalRequest $request, RentalUsageService $service): AnonymousResourceCollection
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::VIEW,
        );

        return RentalUsageLogResource::collection($service->paginate(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated(),
            $request->perPage(),
        ));
    }

    public function store(StoreRentalUsageRequest $request, int $allocation, RentalUsageService $service): JsonResponse
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_USAGE,
        );
        $allocationModel = $this->scope(RentalVehicleAllocation::query(), $request)->findOrFail($allocation);

        return (new RentalUsageLogResource($service->create(
            $allocationModel,
            $request->validated(),
            $request->currentUserId(),
        )))->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $usage, RentalUsageService $service): RentalUsageLogResource
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::VIEW,
        );

        return new RentalUsageLogResource(
            $this->scope(RentalUsageLog::query(), $request)
                ->with($service->relations())
                ->findOrFail($usage),
        );
    }

    public function transition(
        RentalUsageTransitionRequest $request,
        int $usage,
        RentalUsageService $service,
    ): RentalUsageLogResource {
        $status = RentalUsageStatus::from((string) $request->input('status'));
        $permission = in_array(
            $status,
            [RentalUsageStatus::Approved, RentalUsageStatus::Rejected, RentalUsageStatus::Reversed],
            true,
        )
            ? VehicleRentalAuthorizationService::APPROVE_USAGE
            : VehicleRentalAuthorizationService::RECORD_USAGE;
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $permission);

        return new RentalUsageLogResource($service->transition(
            $this->scope(RentalUsageLog::query(), $request)->findOrFail($usage),
            $status,
            (int) $request->input('expected_version'),
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }
}
