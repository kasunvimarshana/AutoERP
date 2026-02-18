<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Http\Requests\StorePaymentRequest;
use Modules\Accounting\Http\Requests\UpdatePaymentRequest;
use Modules\Accounting\Http\Resources\PaymentResource;
use Modules\Accounting\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $payments = $this->paymentService->getPaginated($perPage);

        return PaymentResource::collection($payments)->response();
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->create($request->validated());

            return (new PaymentResource($payment))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show(int $id): JsonResponse
    {
        $payment = $this->paymentService->findById($id);
        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        return (new PaymentResource($payment))->response();
    }

    public function update(UpdatePaymentRequest $request, int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->update($id, $request->validated());

            return (new PaymentResource($payment))->response();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->paymentService->delete($id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function applyToInvoice(int $id, Request $request): JsonResponse
    {
        try {
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $payment = $this->paymentService->applyToInvoice($id, $invoiceId, $amount);

            return (new PaymentResource($payment))->response();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function process(int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->process($id);

            return (new PaymentResource($payment))->response();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
