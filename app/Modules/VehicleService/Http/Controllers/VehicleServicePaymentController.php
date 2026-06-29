<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\PrepareVehicleServicePaymentRequest;
use Modules\VehicleService\Services\VehicleServicePaymentIntegrationService;
use Modules\VehicleService\Services\VehicleServicePaymentOptionService;

final class VehicleServicePaymentController extends VehicleServiceController
{
    public function options(
        ListVehicleServiceJobRequest $request,
        int $job,
        VehicleServicePaymentOptionService $service,
    ): JsonResponse {
        $serviceJob = $this->job($request, $job);

        return response()->json([
            'data' => [
                'job_version' => (int) $serviceJob->row_version,
                ...$service->options($serviceJob),
            ],
        ]);
    }

    public function prepare(
        PrepareVehicleServicePaymentRequest $request,
        int $job,
        VehicleServicePaymentIntegrationService $service,
    ): JsonResponse {
        return response()->json([
            'data' => $service->prepare(
                $this->job($request, $job),
                $request->toData(),
            ),
        ]);
    }

    public function store(
        PrepareVehicleServicePaymentRequest $request,
        int $job,
        VehicleServicePaymentIntegrationService $service,
    ): JsonResponse {
        return (new PaymentResource($service->create(
            $this->job($request, $job),
            $request->toData(),
        )))->response()->setStatusCode(201);
    }
}
