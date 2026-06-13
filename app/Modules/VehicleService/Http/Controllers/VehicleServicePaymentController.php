<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\VehicleService\Http\Requests\PrepareVehicleServicePaymentRequest;
use Modules\VehicleService\Services\VehicleServicePaymentIntegrationService;

final class VehicleServicePaymentController extends VehicleServiceController
{
    public function prepare(
        PrepareVehicleServicePaymentRequest $request,
        int $job,
        VehicleServicePaymentIntegrationService $service,
    ): JsonResponse {
        return response()->json(['data' => $service->prepare(
            $this->job($request, $job),
            (int) $request->input('invoice_id'),
            (string) $request->input('payment_date'),
            (string) $request->input('amount'),
            $request->filled('payment_method_id') ? (int) $request->input('payment_method_id') : null,
            $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            (string) $request->input('exchange_rate', '1.000000'),
            $request->filled('reference_number') ? (string) $request->input('reference_number') : null,
            $request->currentUserId(),
        )]);
    }

    public function store(
        PrepareVehicleServicePaymentRequest $request,
        int $job,
        VehicleServicePaymentIntegrationService $service,
    ): JsonResponse {
        return (new PaymentResource($service->create(
            $this->job($request, $job),
            (int) $request->input('invoice_id'),
            (string) $request->input('payment_date'),
            (string) $request->input('amount'),
            $request->filled('payment_method_id') ? (int) $request->input('payment_method_id') : null,
            $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            (string) $request->input('exchange_rate', '1.000000'),
            $request->filled('reference_number') ? (string) $request->input('reference_number') : null,
            $request->currentUserId(),
        )))->response()->setStatusCode(201);
    }
}
