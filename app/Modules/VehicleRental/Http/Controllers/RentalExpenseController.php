<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalActionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalExpenseRequest;
use Modules\VehicleRental\Http\Resources\RentalExpenseResource;
use Modules\VehicleRental\Services\RentalExpenseService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalExpenseController extends RentalController
{
    public function index(ListRentalRequest $request, int $agreement): AnonymousResourceCollection
    {
        return RentalExpenseResource::collection(
            $this->agreement($request, $agreement)->expenses()->latest('id')->get(),
        );
    }

    public function store(
        StoreRentalExpenseRequest $request,
        int $agreement,
        RentalExpenseService $service,
    ): JsonResponse {
        return (new RentalExpenseResource($service->create(
            $this->agreement($request, $agreement),
            $request->toData(),
        )))->response()->setStatusCode(201);
    }

    public function approve(
        RentalActionRequest $request,
        int $agreement,
        int $expense,
        RentalExpenseService $service,
        VehicleRentalAuthorizationService $authorization,
    ): RentalExpenseResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::APPROVE_EXPENSES,
        );
        $model = $this->agreement($request, $agreement);

        return new RentalExpenseResource($service->changeStatus(
            $this->expense($model, $expense),
            RentalExpenseStatus::Approved,
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }

    public function reject(
        RentalActionRequest $request,
        int $agreement,
        int $expense,
        RentalExpenseService $service,
        VehicleRentalAuthorizationService $authorization,
    ): RentalExpenseResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::APPROVE_EXPENSES,
        );
        $model = $this->agreement($request, $agreement);

        return new RentalExpenseResource($service->changeStatus(
            $this->expense($model, $expense),
            RentalExpenseStatus::Rejected,
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }
}
