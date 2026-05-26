<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\VehicleService\Application\DTOs\CreateServicePaymentDTO;
use Modules\VehicleService\Application\Orchestrators\VehicleServiceOrchestrator;
use Modules\VehicleService\Presentation\Http\Requests\UpsertVehicleServicePaymentRequest;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceJobCardResource;
use Throwable;

final class VehicleServicePaymentController extends Controller
{
    public function __construct(private readonly VehicleServiceOrchestrator $orchestrator)
    {
    }

    public function store(UpsertVehicleServicePaymentRequest $request): JsonResponse|VehicleServiceJobCardResource
    {
        try {
            return (new VehicleServiceJobCardResource(
                $this->orchestrator->payment(new CreateServicePaymentDTO($request->validated())),
            ))->response()->setStatusCode(201);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
