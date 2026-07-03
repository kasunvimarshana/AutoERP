<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleService\Http\Requests\CreateVehicleServiceInvoiceRequest;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceJobLineResource;
use Modules\VehicleService\Services\VehicleServiceInvoiceIntegrationService;

final class VehicleServiceInvoiceController extends VehicleServiceController
{
    public function preview(
        CreateVehicleServiceInvoiceRequest $request,
        int $job,
        VehicleServiceInvoiceIntegrationService $service,
    ): JsonResponse {
        return response()->json(['data' => get_object_vars($service->preview(
            $this->job($request, $job),
            (string) $request->input('invoice_date'),
            $request->lineQuantities(),
        ))]);
    }

    public function store(
        CreateVehicleServiceInvoiceRequest $request,
        int $job,
        VehicleServiceInvoiceIntegrationService $service,
    ): JsonResponse {
        return response()->json(['data' => $service->create(
            $this->job($request, $job),
            (string) $request->input('invoice_date'),
            $request->lineQuantities(),
            $request->filled('due_date') ? (string) $request->input('due_date') : null,
            $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            (string) $request->input('exchange_rate', '1.000000'),
            $request->filled('notes') ? (string) $request->input('notes') : null,
            $request->currentUserId(),
            $request->expectedVersion(),
        )], 201);
    }

    public function lines(
        ListVehicleServiceJobRequest $request,
        int $job,
        VehicleServiceInvoiceIntegrationService $service,
    ): AnonymousResourceCollection {
        return VehicleServiceJobLineResource::collection(
            $service->billableLines($this->job($request, $job)),
        );
    }
}
