<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Modules\Invoice\Services\InvoiceService;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Http\Requests\Invoice\RecordPaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends BaseController
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $invoices = $this->invoiceService->getAll([], $perPage);

            return $this->paginatedResponse(
                InvoiceResource::collection($invoices),
                'Invoices retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve invoices: ' . $e->getMessage());
        }
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->createInvoiceWithItems($request->validated());

            return $this->successResponse(
                new InvoiceResource($invoice),
                'Invoice created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create invoice: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->findOrFail($id);
            return $this->successResponse(new InvoiceResource($invoice));
        } catch (\Exception $e) {
            return $this->errorResponse('Invoice not found', 404);
        }
    }

    public function update(UpdateInvoiceRequest $request, int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->updateInvoiceWithItems($id, $request->validated());
            return $this->successResponse(new InvoiceResource($invoice), 'Invoice updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update invoice: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->invoiceService->delete($id);
            return $this->successResponse(null, 'Invoice deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete invoice: ' . $e->getMessage());
        }
    }

    public function approve(int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->approveInvoice($id);
            return $this->successResponse(new InvoiceResource($invoice), 'Invoice approved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve invoice: ' . $e->getMessage());
        }
    }

    public function recordPayment(RecordPaymentRequest $request, int $id): JsonResponse
    {
        try {
            $payment = $this->invoiceService->recordPayment($id, $request->validated());
            return $this->successResponse($payment, 'Payment recorded successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to record payment: ' . $e->getMessage());
        }
    }

    public function createFromSalesOrder(int $salesOrderId): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->createFromSalesOrder($salesOrderId);
            return $this->successResponse(new InvoiceResource($invoice), 'Invoice created from order successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create invoice from order: ' . $e->getMessage());
        }
    }
}
