<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Vehicle\Application\Contracts\UpdateVehicleStatusServiceInterface;
use Modules\Vehicle\Application\Contracts\VehicleDashboardServiceInterface;
use Modules\Vehicle\Infrastructure\Http\Requests\ListVehicleDocumentAlertRequest;
use Modules\Vehicle\Infrastructure\Http\Requests\UpdateVehicleStatusRequest;
use Modules\Vehicle\Infrastructure\Http\Resources\VehicleResource;

class VehicleDashboardController
{
    public function __construct(
        private readonly VehicleDashboardServiceInterface $dashboardService,
        private readonly UpdateVehicleStatusServiceInterface $updateVehicleStatusService,
    ) {}

    public function dashboard(ListVehicleDocumentAlertRequest $request): JsonResponse
    {
        return response()->json($this->dashboardService->execute($request->validated()));
    }

    public function updateStatus(UpdateVehicleStatusRequest $request, int $vehicle): VehicleResource
    {
        $payload = $request->validated();
        $payload['vehicle_id'] = $vehicle;

        return new VehicleResource($this->updateVehicleStatusService->execute($payload));
    }
}
