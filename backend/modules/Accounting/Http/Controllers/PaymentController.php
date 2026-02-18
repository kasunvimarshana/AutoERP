<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Services\PaymentService;
use Modules\Core\Http\Controllers\BaseController;

/**
 * Payment Controller
 *
 * Manages customer payments and payment allocations to invoices.
 * Supports multiple payment methods and automatic invoice status updates.
 */
class PaymentController extends BaseController
{
    /**
     * Constructor
     */
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/accounting/payments",
     *     summary="List all payments",
     *     description="Retrieve paginated list of payments with filtering by status, customer, payment method, date range, and search capabilities",
     *     operationId="paymentsIndex",
     *     tags={"Accounting-Payments"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by payment status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "completed", "failed", "refunded", "cancelled"}, example="completed")
     *     ),
     *     @OA\Parameter(
     *         name="customer_id",
     *         in="query",
     *         description="Filter by customer ID",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440060")
     *     ),
     *     @OA\Parameter(
     *         name="payment_method",
     *         in="query",
     *         description="Filter by payment method",
     *         required=false,
     *         @OA\Schema(type="string", enum={"cash", "bank_transfer", "credit_card", "debit_card", "check", "online", "mobile", "other"}, example="bank_transfer")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter payments from this date (inclusive)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter payments to this date (inclusive)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in payment number, reference, or notes",
     *         required=false,
     *         @OA\Schema(type="string", example="PAY-2024")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payments retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Payment")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", example="http://api.example.com/api/accounting/payments?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://api.example.com/api/accounting/payments?page=10"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", example="http://api.example.com/api/accounting/payments?page=2")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="to", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=150)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->input('status'),
                'customer_id' => $request->input('customer_id'),
                'payment_method' => $request->input('payment_method'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'search' => $request->input('search'),
                'per_page' => $request->integer('per_page', 15),
            ];

            $payments = $this->paymentService->getAll($filters);

            return $this->success($payments);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch payments: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/accounting/payments",
     *     summary="Record a new payment",
     *     description="Record a new customer payment. Optionally allocate the payment to specific invoices. Unallocated amount will remain as credit on account.",
     *     operationId="paymentsStore",
     *     tags={"Accounting-Payments"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payment data with optional invoice allocations",
     *         @OA\JsonContent(ref="#/components/schemas/StorePaymentRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment recorded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid allocation or amount exceeds invoice balance",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid customer, invoice, or amount",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|uuid|exists:customers,id',
                'payment_method' => 'required|string|in:cash,bank_transfer,credit_card,debit_card,check,online,mobile,other',
                'payment_date' => 'required|date',
                'amount' => 'required|numeric|min:0.01',
                'currency_code' => 'nullable|string|size:3',
                'reference' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'allocations' => 'nullable|array',
                'allocations.*.invoice_id' => 'required|uuid|exists:invoices,id',
                'allocations.*.amount' => 'required|numeric|min:0.01',
                'allocations.*.notes' => 'nullable|string',
            ]);

            $payment = $this->paymentService->create($validated);

            return $this->created($payment, 'Payment created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create payment: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/accounting/payments/{id}",
     *     summary="Get payment details",
     *     description="Retrieve detailed information for a specific payment including all invoice allocations",
     *     operationId="paymentsShow",
     *     tags={"Accounting-Payments"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440090")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->getById($id);

            return $this->success($payment);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Payment not found');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch payment: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/accounting/payments/{id}",
     *     summary="Update a payment",
     *     description="Update an existing payment. Only pending payments can be fully edited. Completed payments have limited update options.",
     *     operationId="paymentsUpdate",
     *     tags={"Accounting-Payments"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440090")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payment data to update",
     *         @OA\JsonContent(ref="#/components/schemas/UpdatePaymentRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Cannot update completed or refunded payment",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'sometimes|uuid|exists:customers,id',
                'payment_method' => 'sometimes|string|in:cash,bank_transfer,credit_card,debit_card,check,online,mobile,other',
                'status' => 'sometimes|string|in:pending,completed,failed,refunded,cancelled',
                'payment_date' => 'sometimes|date',
                'amount' => 'sometimes|numeric|min:0.01',
                'currency_code' => 'nullable|string|size:3',
                'reference' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);

            $payment = $this->paymentService->update($id, $validated);

            return $this->updated($payment, 'Payment updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Payment not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update payment: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/accounting/payments/{id}/allocate",
     *     summary="Allocate payment to invoices",
     *     description="Allocate or re-allocate payment amounts to specific invoices. This updates invoice balances and statuses automatically. Total allocations cannot exceed payment amount.",
     *     operationId="paymentsAllocate",
     *     tags={"Accounting-Payments"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440090")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payment allocation data",
     *         @OA\JsonContent(ref="#/components/schemas/AllocatePaymentRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment allocated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment allocated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Allocation exceeds payment amount or invoice already paid",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment or invoice not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid invoice or amount",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function allocate(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'allocations' => 'required|array|min:1',
                'allocations.*.invoice_id' => 'required|uuid|exists:invoices,id',
                'allocations.*.amount' => 'required|numeric|min:0.01',
                'allocations.*.notes' => 'nullable|string',
            ]);

            $payment = $this->paymentService->getById($id);
            $payment = $this->paymentService->allocatePayment($payment, $validated['allocations']);

            return $this->updated($payment, 'Payment allocated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Payment not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to allocate payment: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/accounting/payments/{id}/complete",
     *     summary="Mark payment as completed",
     *     description="Mark a pending payment as completed. This finalizes the payment and updates all allocated invoice statuses. This action is typically used after payment verification.",
     *     operationId="paymentsComplete",
     *     tags={"Accounting-Payments"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440090")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment marked as completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment marked as completed"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Payment not in pending status",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function complete(string $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->markAsCompleted($id);

            return $this->updated($payment, 'Payment marked as completed');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Payment not found');
        } catch (\Exception $e) {
            return $this->error('Failed to complete payment: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/accounting/payments/{id}/cancel",
     *     summary="Cancel a payment",
     *     description="Cancel a payment and reverse all invoice allocations. This updates the status to 'cancelled' and restores invoice balances. Use for failed or voided payments.",
     *     operationId="paymentsCancel",
     *     tags={"Accounting-Payments"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440090")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment cancelled successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Payment already cancelled or refunded",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->cancel($id);

            return $this->updated($payment, 'Payment cancelled successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Payment not found');
        } catch (\Exception $e) {
            return $this->error('Failed to cancel payment: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/accounting/payments/{id}",
     *     summary="Delete a payment",
     *     description="Delete a payment record. Only pending or failed payments can be deleted. Completed payments should be cancelled or refunded instead to maintain audit trail.",
     *     operationId="paymentsDestroy",
     *     tags={"Accounting-Payments"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440090")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Payment deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Cannot delete completed or refunded payment",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->paymentService->delete($id);

            return $this->deleted('Payment deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Payment not found');
        } catch (\Exception $e) {
            return $this->error('Failed to delete payment: '.$e->getMessage(), 500);
        }
    }
}
