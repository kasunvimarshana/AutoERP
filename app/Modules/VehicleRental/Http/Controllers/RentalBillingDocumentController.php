<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Http\Requests\RentalBillingDocumentRequest;
use Modules\VehicleRental\Services\RentalBillingDocumentService;
use Symfony\Component\HttpFoundation\Response;

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

    public function quotationPreview(RentalBillingDocumentRequest $request): JsonResponse
    {
        $data = $this->billingService->previewQuotation($request->context(), $request->validated());

        return response()->json(['data' => $data]);
    }

    public function previewCustomerQuotation(RentalBillingDocumentRequest $request): JsonResponse
    {
        return $this->quotationPreview($request);
    }

    public function ownerBillPreview(RentalBillingDocumentRequest $request): JsonResponse
    {
        $data = $this->billingService->previewOwnerBill($request->context(), $request->validated());

        return response()->json(['data' => $data]);
    }

    public function previewOwnerBill(RentalBillingDocumentRequest $request): JsonResponse
    {
        return $this->ownerBillPreview($request);
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

    public function statement(RentalBillingDocumentRequest $request, int $invoice): Response
    {
        $format = (string) $request->input('format', 'html');
        $data = $this->billingService->getStatementData($request->context(), $invoice);

        $viewName = 'reports.vehicle-rental.billing-cycle-statement';
        $filename = "billing-statement-{$data['header']['cycle_number']}.pdf";

        if ($format === 'pdf') {
            return \Spatie\LaravelPdf\Facades\Pdf::view($viewName, array_merge($data, ['mode' => 'pdf']))
                ->format('A4')
                ->orientation('portrait')
                ->margins(8, 10, 8, 10)
                ->name($filename)
                ->download()
                ->toResponse($request);
        }

        if ($format === 'print') {
            return response()->view(
                $viewName,
                array_merge($data, ['mode' => 'print']),
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        }

        return response()->view(
            $viewName,
            array_merge($data, ['mode' => 'html']),
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }
}
