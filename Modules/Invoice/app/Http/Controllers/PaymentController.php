<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Invoice\Requests\RecordPaymentRequest;
use Modules\Invoice\Resources\PaymentResource;
use Modules\Invoice\Services\PaymentService;

/**
 * Payment Controller
 *
 * Handles HTTP requests for Payment operations
 */
class PaymentController extends Controller
{
    /**
     * PaymentController constructor
     */
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Display a listing of payments
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'invoice_id' => $request->input('invoice_id'),
            'payment_method' => $request->input('payment_method'),
            'status' => $request->input('status'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ];

        $payments = $this->paymentService->getHistory($filters);

        return $this->successResponse(
            PaymentResource::collection($payments),
            __('invoice::messages.payments_retrieved')
        );
    }

    /**
     * Record a new payment
     */
    public function store(RecordPaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->recordPayment($request->validated());

        return $this->createdResponse(
            new PaymentResource($payment),
            __('invoice::messages.payment_recorded')
        );
    }

    /**
     * Display the specified payment
     */
    public function show(int $id): JsonResponse
    {
        $payment = $this->paymentService->getWithRelations($id);

        return $this->successResponse(
            new PaymentResource($payment),
            __('invoice::messages.payment_retrieved')
        );
    }

    /**
     * Void a payment
     */
    public function void(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $payment = $this->paymentService->voidPayment($id, $request->input('notes'));

        return $this->successResponse(
            new PaymentResource($payment),
            __('invoice::messages.payment_voided')
        );
    }

    /**
     * Get payment history for an invoice
     */
    public function historyForInvoice(int $invoiceId): JsonResponse
    {
        $payments = $this->paymentService->getForInvoice($invoiceId);

        return $this->successResponse(
            PaymentResource::collection($payments),
            __('invoice::messages.payment_history_retrieved')
        );
    }
}
