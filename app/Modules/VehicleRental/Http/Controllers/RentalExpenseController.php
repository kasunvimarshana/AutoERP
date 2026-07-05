<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalTransitionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalExpenseRequest;
use Modules\VehicleRental\Http\Resources\RentalExpenseResource;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Services\RentalExpenseService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalExpenseController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function index(ListRentalRequest $request, RentalExpenseService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW_FINANCIAL);
        return RentalExpenseResource::collection($service->paginate($request->tenantId(), $request->organizationUnitId(), $request->validated(), $request->perPage()));
    }

    public function store(StoreRentalExpenseRequest $request, RentalExpenseService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::RECORD_EXPENSES);
        return (new RentalExpenseResource($service->create($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->currentUserId())))->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $expense, RentalExpenseService $service): RentalExpenseResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW_FINANCIAL);
        return new RentalExpenseResource($this->scope(RentalExpense::query(), $request)->with($service->relations())->findOrFail($expense));
    }

    public function transition(RentalTransitionRequest $request, int $expense, RentalExpenseService $service): RentalExpenseResource
    {
        $status = RentalExpenseStatus::from((string) $request->input('status'));
        $permission = in_array($status, [RentalExpenseStatus::Approved, RentalExpenseStatus::Rejected, RentalExpenseStatus::Reversed], true)
            ? VehicleRentalAuthorizationService::APPROVE_EXPENSES
            : VehicleRentalAuthorizationService::RECORD_EXPENSES;
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $permission);
        return new RentalExpenseResource($service->transition(
            $this->scope(RentalExpense::query(), $request)->findOrFail($expense),
            $status,
            (int) $request->input('expected_version'),
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }
}
