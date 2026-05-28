<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Payment\Application\Contracts\UseCases\PaymentEngines\AllocatePaymentDocumentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentEngines\SettlePaymentStatusServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentEngines\UnallocatePaymentDocumentServiceInterface;
use Modules\Payment\Application\Contracts\Services\PaymentPostingServiceInterface;
use Modules\Payment\Application\Contracts\Services\PaymentReversalServiceInterface;
use Modules\Payment\Application\Contracts\Services\RefundServiceInterface;
use Modules\Payment\Presentation\Http\Requests\AllocatePaymentDocumentRequest;
use Modules\Payment\Presentation\Http\Requests\SettlePaymentStatusRequest;
use Modules\Payment\Presentation\Http\Requests\UnallocatePaymentDocumentRequest;

final class PaymentEngineController extends Controller
{
    public function __construct(
        private readonly AllocatePaymentDocumentServiceInterface $allocatePaymentDocumentService,
        private readonly UnallocatePaymentDocumentServiceInterface $unallocatePaymentDocumentService,
        private readonly SettlePaymentStatusServiceInterface $settlePaymentStatusService,
        private readonly PaymentPostingServiceInterface $paymentPostingService,
        private readonly PaymentReversalServiceInterface $paymentReversalService,
        private readonly RefundServiceInterface $refundService,
    ) {
    }

    public function allocate(
        AllocatePaymentDocumentRequest $request,
        int|string $payment,
    ): JsonResponse {
        $result = $this->allocatePaymentDocumentService->execute($payment, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422;

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function unallocate(
        UnallocatePaymentDocumentRequest $request,
        int|string $payment,
    ): JsonResponse {
        $result = $this->unallocatePaymentDocumentService->execute($payment, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422;

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function settleStatus(
        SettlePaymentStatusRequest $request,
        int|string $payment,
    ): JsonResponse {
        $result = $this->settlePaymentStatusService->execute($payment, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422;

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function post(int|string $payment): JsonResponse
    {
        $result = $this->paymentPostingService->postPayment($payment, []);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function reverse(int|string $payment): JsonResponse
    {
        $result = $this->paymentReversalService->reversePayment($payment, request()->all());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function refund(int|string $payment): JsonResponse
    {
        $result = $this->refundService->refundPayment($payment, request()->all());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
