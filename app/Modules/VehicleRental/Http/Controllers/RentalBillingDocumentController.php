<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Http\Requests\RentalBillingDocumentRequest;
use Modules\VehicleRental\Services\RentalBillingDocumentService;

final class RentalBillingDocumentController
{
    public function __construct(private readonly RentalBillingDocumentService $billingService) {}

    public function index(RentalBillingDocumentRequest $request): JsonResponse
    {
        $paginator = $this->billingService->listDocuments(
            $request->context(),
            $request->validated(),
            (int) $request->input('per_page', 25)
        );

        return response()->json($paginator);
    }

    public function summary(RentalBillingDocumentRequest $request): JsonResponse
    {
        $summary = $this->billingService->getSummary($request->context());

        return response()->json(['data' => $summary]);
    }

    public function show(RentalBillingDocumentRequest $request, int $invoice): JsonResponse
    {
        $document = $this->billingService->getDocumentDetail($request->context(), $invoice);

        return response()->json(['data' => $document]);
    }

    public function update(RentalBillingDocumentRequest $request, int $invoice): JsonResponse
    {
        $document = $this->billingService->updateDocument(
            $request->context(),
            $invoice,
            $request->validated()
        );

        return response()->json(['data' => $document]);
    }

    public function cancel(RentalBillingDocumentRequest $request, int $invoice): JsonResponse
    {
        $document = $this->billingService->cancelDocument(
            $request->context(),
            $invoice,
            $request->expectedVersion(),
            (string) $request->input('reason')
        );

        return response()->json(['data' => $document]);
    }
}
