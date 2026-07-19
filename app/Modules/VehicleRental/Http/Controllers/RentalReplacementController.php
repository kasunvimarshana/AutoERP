<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalReplacementRequest;
use Modules\VehicleRental\Http\Resources\RentalAllocationResource;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Models\RentalVehicleReplacement;
use Modules\VehicleRental\Services\RentalReplacementService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalReplacementController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function store(StoreRentalReplacementRequest $request, int $allocation, RentalReplacementService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_REPLACEMENTS);
        $old = $this->scope(RentalVehicleAllocation::query(), $request)->findOrFail($allocation);
        $replacement = $service->replace($old, $request->validated(), $request->currentUserId());

        return response()->json(['data' => [
            'id' => (int) $replacement->getKey(),
            'replacement_number' => $replacement->replacement_number,
            'status' => $replacement->status->value,
            'replacement_at' => $replacement->replacement_at?->toISOString(),
            'old_allocation' => (new RentalAllocationResource($replacement->oldAllocation))->resolve($request),
            'new_allocation' => (new RentalAllocationResource($replacement->newAllocation))->resolve($request),
        ]], 201);
    }

    public function show(ListRentalRequest $request, int $replacement): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW);
        $model = $this->scope(RentalVehicleReplacement::query(), $request)->with([
            'agreement.customer', 'oldAllocation.vehicle', 'oldAllocation.custodyEvents.items',
            'newAllocation.vehicle', 'newAllocation.custodyEvents.items',
        ])->findOrFail($replacement);

        return response()->json(['data' => [
            'id' => (int) $model->getKey(),
            'replacement_number' => $model->replacement_number,
            'replacement_at' => $model->replacement_at?->toISOString(),
            'reason_code' => $model->reason_code,
            'reason' => $model->reason,
            'status' => $model->status->value,
            'old_allocation' => (new RentalAllocationResource($model->oldAllocation))->resolve($request),
            'new_allocation' => (new RentalAllocationResource($model->newAllocation))->resolve($request),
        ]]);
    }
}
