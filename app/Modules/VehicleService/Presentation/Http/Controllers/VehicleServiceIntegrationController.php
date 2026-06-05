<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\Services\VehicleServiceIntegrationServiceInterface;

final class VehicleServiceIntegrationController extends Controller
{
    public function __construct(private readonly VehicleServiceIntegrationServiceInterface $service)
    {
    }

    public function allocateServicePayment(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->allocateServicePayment($jobCardId, $request->all()));
    }

    public function postServiceInventory(Request $request, int $jobCardId): JsonResponse
    {
        return $this->respond($this->service->postServiceInventory($jobCardId, $request->all()));
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $statusCode = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $statusCode);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
