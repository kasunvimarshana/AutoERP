<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalUsageEventRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalUsageLogRequest;
use Modules\VehicleRental\Http\Resources\RentalUsageEventResource;
use Modules\VehicleRental\Http\Resources\RentalUsageLogResource;
use Modules\VehicleRental\Services\RentalUsageEventService;
use Modules\VehicleRental\Services\RentalUsageLogService;

final class RentalUsageController extends RentalController
{
    public function index(ListRentalRequest $request, int $agreement): AnonymousResourceCollection
    {
        return RentalUsageLogResource::collection(
            $this->agreement($request, $agreement)->usageLogs()
                ->with(['vehicle.make', 'vehicle.model', 'driver', 'events'])
                ->get(),
        );
    }

    public function store(
        StoreRentalUsageLogRequest $request,
        int $agreement,
        RentalUsageLogService $service,
    ): JsonResponse {
        return (new RentalUsageLogResource($service->create(
            $this->agreement($request, $agreement),
            $request->toData(),
        )))->response()->setStatusCode(201);
    }

    public function storeEvent(
        StoreRentalUsageEventRequest $request,
        int $agreement,
        int $usageLog,
        RentalUsageEventService $service,
    ): JsonResponse {
        $model = $this->agreement($request, $agreement);

        return (new RentalUsageEventResource($service->create(
            $this->usageLog($model, $usageLog),
            $request->toData(),
        )))->response()->setStatusCode(201);
    }
}
