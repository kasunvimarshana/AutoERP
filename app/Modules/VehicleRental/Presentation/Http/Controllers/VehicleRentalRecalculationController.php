<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\VehicleRental\Application\Services\VehicleRentalService;
use Modules\VehicleRental\Domain\Exceptions\VehicleRentalRecordNotFoundException;
use Modules\VehicleRental\Presentation\Http\Resources\VehicleRentalRecordResource;

class VehicleRentalRecalculationController extends Controller
{
    public function __construct(private readonly VehicleRentalService $vehicleRentals) {}

    public function lessorRunningChart(int|string $tenant, int|string $runningChart): VehicleRentalRecordResource|JsonResponse
    {
        try {
            return new VehicleRentalRecordResource($this->vehicleRentals->recalculateLessorRunningChart($tenant, $runningChart));
        } catch (VehicleRentalRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function lesseeRunningChart(int|string $tenant, int|string $runningChart): VehicleRentalRecordResource|JsonResponse
    {
        try {
            return new VehicleRentalRecordResource($this->vehicleRentals->recalculateLesseeRunningChart($tenant, $runningChart));
        } catch (VehicleRentalRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
