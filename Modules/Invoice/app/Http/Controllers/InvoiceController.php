<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Invoice\Requests\GenerateInvoiceRequest;
use Modules\Invoice\Requests\StoreInvoiceRequest;
use Modules\Invoice\Requests\UpdateInvoiceRequest;
use Modules\Invoice\Resources\InvoiceResource;
use Modules\Invoice\Services\InvoiceService;

/**
 * Invoice Controller
 *
 * Handles HTTP requests for Invoice operations
 */
class InvoiceController extends Controller
{
    /**
     * InvoiceController constructor
     */
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}

    /**
     * Display a listing of invoices
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
            'status' => $request->input('status'),
            'customer_id' => $request->input('customer_id'),
            'branch_id' => $request->input('branch_id'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'outstanding' => $request->boolean('outstanding'),
            'overdue' => $request->boolean('overdue'),
        ];

        $invoices = $this->invoiceService->getAll($filters);

        return $this->successResponse(
            InvoiceResource::collection($invoices),
            __('invoice::messages.invoices_retrieved')
        );
    }

    /**
     * Store a newly created invoice
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->create($request->validated());

        return $this->createdResponse(
            new InvoiceResource($invoice),
            __('invoice::messages.invoice_created')
        );
    }

    /**
     * Display the specified invoice
     */
    public function show(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->getWithRelations($id);

        return $this->successResponse(
            new InvoiceResource($invoice),
            __('invoice::messages.invoice_retrieved')
        );
    }

    /**
     * Update the specified invoice
     */
    public function update(UpdateInvoiceRequest $request, int $id): JsonResponse
    {
        $invoice = $this->invoiceService->update($id, $request->validated());

        return $this->successResponse(
            new InvoiceResource($invoice),
            __('invoice::messages.invoice_updated')
        );
    }

    /**
     * Remove the specified invoice
     */
    public function destroy(int $id): JsonResponse
    {
        $this->invoiceService->delete($id);

        return $this->successResponse(
            null,
            __('invoice::messages.invoice_deleted')
        );
    }

    /**
     * Generate invoice from job card
     */
    public function generateFromJobCard(GenerateInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->generateFromJobCard(
            $request->input('job_card_id'),
            $request->except('job_card_id')
        );

        return $this->createdResponse(
            new InvoiceResource($invoice),
            __('invoice::messages.invoice_generated')
        );
    }

    /**
     * Search invoices
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'customer_id',
            'branch_id',
            'from_date',
            'to_date',
            'outstanding',
            'overdue',
        ]);

        $invoices = $this->invoiceService->search($filters);

        return $this->successResponse(
            InvoiceResource::collection($invoices),
            __('invoice::messages.invoices_retrieved')
        );
    }

    /**
     * Get overdue invoices
     */
    public function overdue(): JsonResponse
    {
        $invoices = $this->invoiceService->getOverdue();

        return $this->successResponse(
            InvoiceResource::collection($invoices),
            __('invoice::messages.overdue_invoices_retrieved')
        );
    }

    /**
     * Get outstanding invoices
     */
    public function outstanding(): JsonResponse
    {
        $invoices = $this->invoiceService->getOutstanding();

        return $this->successResponse(
            InvoiceResource::collection($invoices),
            __('invoice::messages.outstanding_invoices_retrieved')
        );
    }
}
