<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\BaseController;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Http\Resources\PaymentResource;
use App\Modules\Payment\Http\Requests\StorePaymentRequest;
use App\Modules\Payment\Http\Requests\UpdatePaymentRequest;
use App\Modules\Payment\Http\Requests\ReconcilePaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends BaseController
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of payments
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $payments = $this->paymentService->getAll([], $perPage);

            return $this->paginatedResponse(
                PaymentResource::collection($payments),
                'Payments retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve payments: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created payment
     *
     * @param StorePaymentRequest $request
     * @return JsonResponse
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->create($request->validated());

            return $this->successResponse(
                new PaymentResource($payment),
                'Payment created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create payment: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified payment
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->getById($id);

            return $this->successResponse(
                new PaymentResource($payment),
                'Payment retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve payment: ' . $e->getMessage(), [], 404);
        }
    }

    /**
     * Update the specified payment
     *
     * @param UpdatePaymentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdatePaymentRequest $request, int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->update($id, $request->validated());

            return $this->successResponse(
                new PaymentResource($payment),
                'Payment updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update payment: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified payment
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->paymentService->delete($id);

            return $this->successResponse(null, 'Payment deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete payment: ' . $e->getMessage());
        }
    }

    /**
     * Reconcile a payment
     *
     * @param ReconcilePaymentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function reconcile(ReconcilePaymentRequest $request, int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->reconcile($id, $request->validated());

            return $this->successResponse(
                new PaymentResource($payment),
                'Payment reconciled successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reconcile payment: ' . $e->getMessage());
        }
    }

    /**
     * Unreconcile a payment
     *
     * @param int $id
     * @return JsonResponse
     */
    public function unreconcile(int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->unreconcile($id);

            return $this->successResponse(
                new PaymentResource($payment),
                'Payment unreconciled successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to unreconcile payment: ' . $e->getMessage());
        }
    }

    /**
     * Complete a payment
     *
     * @param int $id
     * @return JsonResponse
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->complete($id);

            return $this->successResponse(
                new PaymentResource($payment),
                'Payment completed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to complete payment: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a payment
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->cancel($id);

            return $this->successResponse(
                new PaymentResource($payment),
                'Payment cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel payment: ' . $e->getMessage());
        }
    }

    /**
     * Get payment statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'payment_type']);
            $stats = $this->paymentService->getStatistics($filters);

            return $this->successResponse($stats, 'Payment statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve payment statistics: ' . $e->getMessage());
        }
    }

    /**
     * Search payments
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');
            $filters = $request->only(['status', 'payment_type', 'payment_method_id', 'entity_type', 'reconciliation_status', 'start_date', 'end_date']);
            
            $payments = $this->paymentService->search($query, $filters);

            return $this->successResponse(
                PaymentResource::collection($payments),
                'Search results retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search payments: ' . $e->getMessage());
        }
    }
}
