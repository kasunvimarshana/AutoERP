<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Http\Requests\StoreInvoiceRequest;
use Modules\Accounting\Http\Requests\UpdateInvoiceRequest;
use Modules\Accounting\Http\Resources\InvoiceResource;
use Modules\Accounting\Services\InvoiceService;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $invoices = $this->invoiceService->getPaginated($perPage);

        return InvoiceResource::collection($invoices)->response();
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->create($request->validated());

        return (new InvoiceResource($invoice->load('lines')))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->findById($id);
        if (! $invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        return (new InvoiceResource($invoice->load(['lines', 'payments'])))->response();
    }

    public function update(UpdateInvoiceRequest $request, int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->update($id, $request->validated());

            return (new InvoiceResource($invoice->load('lines')))->response();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->invoiceService->delete($id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function send(int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->send($id);

            return (new InvoiceResource($invoice))->response();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function markPaid(int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->markAsPaid($id);

            return (new InvoiceResource($invoice))->response();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function overdue(): JsonResponse
    {
        $invoices = $this->invoiceService->getOverdueInvoices();

        return InvoiceResource::collection($invoices)->response();
    }

    public function aging(): JsonResponse
    {
        $report = $this->invoiceService->getAgingReport();

        return response()->json($report);
    }
}
