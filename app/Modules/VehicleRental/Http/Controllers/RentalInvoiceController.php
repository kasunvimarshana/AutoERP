<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalInvoiceRequest;
use Modules\VehicleRental\Http\Resources\RentalChargeResource;
use Modules\VehicleRental\Services\RentalInvoiceIntegrationService;

final class RentalInvoiceController extends RentalController
{
    public function charges(
        ListRentalRequest $request,
        int $agreement,
        RentalInvoiceIntegrationService $service,
    ): JsonResponse {
        return response()->json([
            'data' => RentalChargeResource::collection(
                $service->billableCharges($this->agreement($request, $agreement)),
            )->resolve($request),
        ]);
    }

    public function preview(
        RentalInvoiceRequest $request,
        int $agreement,
        RentalInvoiceIntegrationService $service,
    ): JsonResponse {
        return response()->json(['data' => $service->preview(
            $this->agreement($request, $agreement),
            (string) $request->input('invoice_date'),
            $request->chargeQuantities(),
            $request->input('due_date'),
        )]);
    }

    public function store(
        RentalInvoiceRequest $request,
        int $agreement,
        RentalInvoiceIntegrationService $service,
    ): JsonResponse {
        $invoice = $service->create(
            $this->agreement($request, $agreement),
            (string) $request->input('invoice_date'),
            $request->chargeQuantities(),
            $request->input('due_date'),
            $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            (string) $request->input('exchange_rate', '1.000000'),
            $request->input('notes'),
            $request->currentUserId(),
        );

        return response()->json(['data' => [
            'id' => (int) $invoice->getKey(),
            'invoice_number' => $invoice->invoice_number,
            'grand_total' => (string) $invoice->grand_total,
        ]], 201);
    }
}
