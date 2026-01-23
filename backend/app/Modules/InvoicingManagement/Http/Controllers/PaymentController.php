<?php

namespace App\Modules\InvoicingManagement\Http\Controllers;

use App\Core\Base\BaseController;
use OpenApi\Attributes as OA;
use App\Modules\InvoicingManagement\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of payments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'search' => $request->input('search'),
                'payment_method' => $request->input('payment_method'),
                'customer_id' => $request->input('customer_id'),
                'invoice_id' => $request->input('invoice_id'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'per_page' => $request->input('per_page', 15),
            ];

            $payments = $this->paymentService->search($criteria);

            return $this->success($payments);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created payment
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $data['tenant_id'] = $request->user()->tenant_id ?? null;

            $payment = $this->paymentService->create($data);

            return $this->created($payment, 'Payment created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified payment
     */
    public function show(int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->findByIdOrFail($id);
            $payment->load(['customer', 'invoice']);

            return $this->success($payment);
        } catch (\Exception $e) {
            return $this->notFound('Payment not found');
        }
    }

    /**
     * Update the specified payment
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->update($id, $request->all());

            return $this->success($payment, 'Payment updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified payment
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->paymentService->delete($id);

            return $this->success(null, 'Payment deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Apply payment to invoice
     */
    public function applyToInvoice(Request $request, int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->applyToInvoice($id, $request->input('invoice_id'));

            return $this->success($payment, 'Payment applied to invoice successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
