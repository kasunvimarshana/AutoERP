<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\KeepVehicleServiceInvoiceOnCreditRequest;
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
        return response()->json([
            'data' => $service->options($this->job($request, $job)),
        ]);
    }

    public function checkCredit(
        ListVehicleServiceJobRequest $request,
        int $job,
        VehicleServicePaymentOptionService $service,
    ): JsonResponse {
        return response()->json([
            'data' => $service->creditAssessmentForConfirmation($this->job($request, $job)),
        ]);
    }

    public function keepInvoiceOnCredit(
        KeepVehicleServiceInvoiceOnCreditRequest $request,
        int $job,
        VehicleServicePaymentIntegrationService $service,
    ): JsonResponse {
        $service->keepInvoiceOnCredit(
            $this->job($request, $job),
            (int) $request->input('invoice_id'),
            $request->expectedVersion(),
        );

        return response()->json(['data' => ['confirmed' => true]]);
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
