<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Base\BaseController;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\DTOs\PaymentDTO;
use App\Modules\Payment\DTOs\PaymentRefundDTO;
use App\Modules\Payment\Http\Requests\StorePaymentRequest;
use App\Modules\Payment\Http\Requests\UpdatePaymentRequest;
use App\Modules\Payment\Http\Requests\RefundPaymentRequest;
use App\Modules\Payment\Http\Requests\CompletePaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment management endpoints"
 * )
 */
class PaymentController extends BaseController
{
    protected PaymentService $service;

    public function __construct(PaymentService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payments",
     *     tags={"Payments"},
     *     summary="List all payments",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payments retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $payments = $this->service->paginate($perPage, ['tenant', 'branch', 'customer', 'invoice', 'refunds']);
            
            return $this->successResponse($payments, 'Payments retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments",
     *     tags={"Payments"},
     *     summary="Create a new payment",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id", "branch_id", "customer_id", "payment_number", "payment_date", "payment_method", "amount"},
     *             @OA\Property(property="tenant_id", type="string", format="uuid"),
     *             @OA\Property(property="branch_id", type="string", format="uuid"),
     *             @OA\Property(property="customer_id", type="string", format="uuid"),
     *             @OA\Property(property="invoice_id", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="payment_number", type="string", example="PAY-2026-001"),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="payment_method", type="string", enum={"cash", "card", "bank_transfer", "cheque", "online", "other"}),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="status", type="string", enum={"pending", "completed", "failed", "refunded", "cancelled"}),
     *             @OA\Property(property="reference_number", type="string"),
     *             @OA\Property(property="transaction_id", type="string"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $dto = PaymentDTO::fromArray($request->validated());
            $payment = $this->service->createPayment($dto);
            
            return $this->successResponse($payment, 'Payment created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payments/{id}",
     *     tags={"Payments"},
     *     summary="Get a specific payment",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $payment = $this->service->findById($id, ['tenant', 'branch', 'customer', 'invoice', 'refunds']);
            
            if (!$payment) {
                return $this->errorResponse('Payment not found', 404);
            }
            
            return $this->successResponse($payment, 'Payment retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/payments/{id}",
     *     tags={"Payments"},
     *     summary="Update a payment",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="branch_id", type="string", format="uuid"),
     *             @OA\Property(property="customer_id", type="string", format="uuid"),
     *             @OA\Property(property="invoice_id", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="payment_number", type="string"),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="payment_method", type="string", enum={"cash", "card", "bank_transfer", "cheque", "online", "other"}),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="currency", type="string"),
     *             @OA\Property(property="status", type="string", enum={"pending", "completed", "failed", "refunded", "cancelled"}),
     *             @OA\Property(property="reference_number", type="string"),
     *             @OA\Property(property="transaction_id", type="string"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="settings", type="object")
     *         )
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
     *         response=404,
     *         description="Payment not found"
     *     )
     * )
     */
    public function update(UpdatePaymentRequest $request, string $id): JsonResponse
    {
        try {
            $dto = PaymentDTO::fromArray($request->validated());
            $result = $this->service->updatePayment($id, $dto);
            
            if (!$result) {
                return $this->errorResponse('Payment not found or update failed', 404);
            }
            
            $payment = $this->service->findById($id, ['tenant', 'branch', 'customer', 'invoice', 'refunds']);
            
            return $this->successResponse($payment, 'Payment updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/payments/{id}",
     *     tags={"Payments"},
     *     summary="Delete a payment",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $result = $this->service->deletePayment($id);
            
            if (!$result) {
                return $this->errorResponse('Payment not found or delete failed', 404);
            }
            
            return $this->successResponse(null, 'Payment deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments/{id}/complete",
     *     tags={"Payments"},
     *     summary="Complete a payment",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="transaction_id", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment completed successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Payment cannot be completed"
     *     )
     * )
     */
    public function complete(CompletePaymentRequest $request, string $id): JsonResponse
    {
        try {
            $transactionId = $request->input('transaction_id');
            $result = $this->service->completePayment($id, $transactionId);
            
            if (!$result) {
                return $this->errorResponse('Payment not found or complete failed', 404);
            }
            
            $payment = $this->service->findById($id, ['tenant', 'branch', 'customer', 'invoice', 'refunds']);
            
            return $this->successResponse($payment, 'Payment completed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments/{id}/refund",
     *     tags={"Payments"},
     *     summary="Refund a payment",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refund_number", "refund_date", "amount", "reason"},
     *             @OA\Property(property="refund_number", type="string", example="REF-2026-001"),
     *             @OA\Property(property="refund_date", type="string", format="date"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="reason", type="string"),
     *             @OA\Property(property="processed_by", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Refund created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Refund created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/PaymentRefund")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Payment cannot be refunded or refund amount exceeds payment amount"
     *     )
     * )
     */
    public function refund(RefundPaymentRequest $request, string $id): JsonResponse
    {
        try {
            $dto = PaymentRefundDTO::fromArray($request->validated());
            $refund = $this->service->refundPayment($id, $dto);
            
            return $this->successResponse($refund, 'Refund created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payments/summary",
     *     tags={"Payments"},
     *     summary="Get payment summary statistics",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         description="Start date (YYYY-MM-DD)",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="End date (YYYY-MM-DD)",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment summary retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_payments", type="integer"),
     *                 @OA\Property(property="total_amount", type="number", format="float"),
     *                 @OA\Property(property="completed_amount", type="number", format="float"),
     *                 @OA\Property(property="pending_amount", type="number", format="float"),
     *                 @OA\Property(property="refunded_amount", type="number", format="float"),
     *                 @OA\Property(property="total_refunds", type="number", format="float"),
     *                 @OA\Property(property="by_method", type="object"),
     *                 @OA\Property(property="by_status", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid date range"
     *     )
     * )
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $from = $request->input('from');
            $to = $request->input('to');

            if (!$from || !$to) {
                return $this->errorResponse('From and To dates are required', 400);
            }

            $summary = $this->service->getPaymentSummary($from, $to);
            
            return $this->successResponse($summary, 'Payment summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
