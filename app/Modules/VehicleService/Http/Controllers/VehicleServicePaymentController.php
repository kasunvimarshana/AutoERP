<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\Payment\Services\PaymentAuthorizationService;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\PrepareVehicleServicePaymentRequest;
use Modules\VehicleService\Services\VehicleServicePaymentIntegrationService;
use Modules\VehicleService\Services\VehicleServicePaymentOptionService;

final class VehicleServicePaymentController extends VehicleServiceController
{
    public function __construct(private readonly PaymentAuthorizationService $authorization) {}

    public function options(
        ListVehicleServiceJobRequest $request,
        int $job,
        VehicleServicePaymentOptionService $service,
    ): JsonResponse {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            PaymentAuthorizationService::PAYMENTS_CREATE,
        );

        return response()->json([
            'data' => $service->options($this->job($request, $job)),
        ]);
    }

    public function prepare(
        PrepareVehicleServicePaymentRequest $request,
        int $job,
        VehicleServicePaymentIntegrationService $service,
    ): JsonResponse {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            PaymentAuthorizationService::PAYMENTS_CREATE,
        );

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
        foreach ([
            PaymentAuthorizationService::PAYMENTS_CREATE,
            PaymentAuthorizationService::PAYMENTS_APPROVE,
            PaymentAuthorizationService::PAYMENTS_POST,
            PaymentAuthorizationService::PAYMENTS_ALLOCATE,
        ] as $permission) {
            $this->authorization->assert($request->currentUserId(), $request->tenantId(), $permission);
        }

        return (new PaymentResource($service->create(
            $this->job($request, $job),
            $request->toData(),
        )))->response()->setStatusCode(201);
    }
}
