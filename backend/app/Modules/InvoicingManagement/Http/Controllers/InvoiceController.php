<?php

namespace App\Modules\InvoicingManagement\Http\Controllers;

use App\Core\Base\BaseController;
use OpenApi\Attributes as OA;
use App\Modules\InvoicingManagement\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends BaseController
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display a listing of invoices
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'customer_id' => $request->input('customer_id'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'per_page' => $request->input('per_page', 15),
            ];

            $invoices = $this->invoiceService->search($criteria);

            return $this->success($invoices);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created invoice
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $data['tenant_id'] = $request->user()->tenant_id ?? null;

            $invoice = $this->invoiceService->create($data);

            return $this->created($invoice, 'Invoice created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->findByIdOrFail($id);
            $invoice->load(['customer', 'jobCard', 'items', 'payments']);

            return $this->success($invoice);
        } catch (\Exception $e) {
            return $this->notFound('Invoice not found');
        }
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->update($id, $request->all());

            return $this->success($invoice, 'Invoice updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified invoice
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->invoiceService->delete($id);

            return $this->success(null, 'Invoice deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Generate invoice from job card
     */
    public function generateFromJobCard(Request $request): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->generateFromJobCard($request->input('job_card_id'));

            return $this->created($invoice, 'Invoice generated successfully from job card');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Send invoice to customer
     */
    public function send(int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->send($id);

            return $this->success($invoice, 'Invoice sent successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Mark invoice as paid
     */
    public function pay(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->pay($id, $request->all());

            return $this->success($invoice, 'Invoice marked as paid successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
