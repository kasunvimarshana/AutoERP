<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Http\Requests\RentalPaymentRequest;
use Modules\VehicleRental\Services\RentalPaymentIntegrationService;

final class RentalPaymentController extends RentalController
{
    public function prepare(
        RentalPaymentRequest $request,
        int $agreement,
        RentalPaymentIntegrationService $service,
    ): JsonResponse {
        return response()->json(['data' => $service->prepare(
            $this->agreement($request, $agreement),
            (string) $request->input('link_type'),
            (string) $request->input('payment_date'),
            (string) $request->input('amount'),
            $request->filled('invoice_id') ? (int) $request->input('invoice_id') : null,
            $request->filled('payment_method_id') ? (int) $request->input('payment_method_id') : null,
            $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            (string) $request->input('exchange_rate', '1.000000'),
            $request->input('reference_number'),
            $request->currentUserId(),
        )]);
    }

    public function store(
        RentalPaymentRequest $request,
        int $agreement,
        RentalPaymentIntegrationService $service,
    ): JsonResponse {
        $payment = $service->create(
            $this->agreement($request, $agreement),
            (string) $request->input('link_type'),
            (string) $request->input('payment_date'),
            (string) $request->input('amount'),
            $request->filled('invoice_id') ? (int) $request->input('invoice_id') : null,
            $request->filled('payment_method_id') ? (int) $request->input('payment_method_id') : null,
            $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            (string) $request->input('exchange_rate', '1.000000'),
            $request->input('reference_number'),
            $request->currentUserId(),
        );

        return response()->json(['data' => [
            'id' => (int) $payment->getKey(),
            'payment_number' => $payment->payment_number,
            'total_amount' => (string) $payment->total_amount,
        ]], 201);
    }
}
