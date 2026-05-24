<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\VehicleService\Application\Services\VehicleServiceService;
use Modules\VehicleService\Domain\Exceptions\VehicleServiceRecordNotFoundException;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceRecordResource;

class VehicleServiceRecalculationController extends Controller
{
    public function __construct(private readonly VehicleServiceService $vehicleServices) {}

    public function jobCard(int|string $tenant, int|string $jobCard): VehicleServiceRecordResource|JsonResponse
    {
        try {
            return new VehicleServiceRecordResource($this->vehicleServices->recalculateJobCard($tenant, $jobCard));
        } catch (VehicleServiceRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
