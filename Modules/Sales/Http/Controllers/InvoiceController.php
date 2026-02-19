<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Sales\Enums\InvoiceStatus;
use Modules\Sales\Events\InvoicePaymentRecorded;
use Modules\Sales\Events\InvoiceSent;
use Modules\Sales\Http\Requests\CancelInvoiceRequest;
use Modules\Sales\Http\Requests\RecordPaymentRequest;
use Modules\Sales\Http\Requests\StoreInvoiceRequest;
use Modules\Sales\Http\Requests\UpdateInvoiceRequest;
use Modules\Sales\Http\Resources\InvoiceResource;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Repositories\InvoiceRepository;
use Modules\Sales\Services\InvoiceService;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private InvoiceService $invoiceService
    ) {}

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = [
            'status' => $request->input('status'),
            'customer_id' => $request->input('customer_id'),
            'organization_id' => $request->input('organization_id'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'overdue' => $request->boolean('overdue'),
            'search' => $request->input('search'),
        ];

        $perPage = $request->get('per_page', 15);
        $invoices = $this->invoiceRepository->getFiltered($filters, $perPage);

        return ApiResponse::paginated(
            $invoices->setCollection(
                $invoices->getCollection()->map(fn ($invoice) => new InvoiceResource($invoice))
            ),
            'Invoices retrieved successfully'
        );
    }

    /**
     * Store a newly created invoice.
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['created_by'] = $request->user()->id;

        $items = $data['items'] ?? [];
        unset($data['items']);

        $invoice = $this->invoiceService->createInvoice($data, $items);
        $invoice->load(['organization', 'customer', 'items.product', 'order']);

        return ApiResponse::created(
            new InvoiceResource($invoice),
            'Invoice created successfully'
        );
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load(['organization', 'customer', 'items.product', 'order', 'payments']);

        return ApiResponse::success(
            new InvoiceResource($invoice),
            'Invoice retrieved successfully'
        );
    }

    /**
     * Update the specified invoice.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $data = $request->validated();
        $invoice = $this->invoiceService->updateInvoice($invoice->id, $data);
        $invoice->load(['organization', 'customer', 'items.product', 'order']);

        return ApiResponse::success(
            new InvoiceResource($invoice),
            'Invoice updated successfully'
        );
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);
        $this->invoiceService->deleteInvoice($invoice->id);

        return ApiResponse::success(
            null,
            'Invoice deleted successfully'
        );
    }

    /**
     * Send the invoice to the customer.
     */
    public function send(Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        if (! $invoice->status->canSend()) {
            return ApiResponse::error(
                'Invoice cannot be sent in its current status',
                422
            );
        }

        $invoice = $this->invoiceService->sendInvoice($invoice->id);
        event(new InvoiceSent($invoice));
        $invoice->load(['organization', 'customer', 'items.product']);

        return ApiResponse::success(
            new InvoiceResource($invoice),
            'Invoice sent successfully'
        );
    }

    /**
     * Record a payment for the invoice.
     */
    public function recordPayment(RecordPaymentRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        if (! $invoice->status->canReceivePayment()) {
            return ApiResponse::error(
                'Invoice cannot receive payment in its current status',
                422
            );
        }

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $invoice = $this->invoiceService->recordPayment($invoice->id, $data);
        $payment = $invoice->payments()->latest()->first();
        event(new InvoicePaymentRecorded($invoice, $payment));
        $invoice->load(['organization', 'customer', 'items.product', 'payments']);

        return ApiResponse::success(
            new InvoiceResource($invoice),
            'Payment recorded successfully'
        );
    }

    /**
     * Cancel the invoice.
     */
    public function cancel(CancelInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status === InvoiceStatus::PAID) {
            return ApiResponse::error(
                'Paid invoices cannot be cancelled',
                422
            );
        }

        if ($invoice->status === InvoiceStatus::CANCELLED) {
            return ApiResponse::error(
                'Invoice is already cancelled',
                422
            );
        }

        $invoice = $this->invoiceService->cancelInvoice($invoice->id, $request->input('reason'));
        $invoice->load(['organization', 'customer', 'items.product']);

        return ApiResponse::success(
            new InvoiceResource($invoice),
            'Invoice cancelled successfully'
        );
    }
}
