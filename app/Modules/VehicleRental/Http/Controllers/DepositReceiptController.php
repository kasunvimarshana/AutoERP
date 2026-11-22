<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Constants\PaymentIdempotency;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Http\Requests\DepositReceiptRequest;
use Modules\VehicleRental\Services\DepositReceipts;
use Symfony\Component\HttpFoundation\Response;

final class DepositReceiptController
{
    public function index(AgreementRequest $request, int $agreement, DepositReceipts $service): JsonResponse
    {
        $summary = $service->summary($request->context(), $agreement);
        $summary['payments'] = PaymentResource::collection($summary['payments']);

        return response()->json(['data' => $summary]);
    }

    public function store(DepositReceiptRequest $request, int $agreement, DepositReceipts $service): JsonResponse
    {
        return (new PaymentResource($service->receive($request->context(), $agreement, (int) $request->validated('expected_version'),
            $request->validated(PaymentIdempotency::REQUEST_ATTRIBUTE), $request->validated(), $request->paymentLineData())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function allocate(AgreementRequest $request, int $agreement, DepositReceipts $service): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'integer', 'min:1'],
            'invoice_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'allocation_date' => ['sometimes', 'date'],
        ]);

        $payment = $service->allocateDeposit(
            $request->context(),
            $agreement,
            (int) $validated['payment_id'],
            (int) $validated['invoice_id'],
            (string) $validated['amount'],
            (string) ($validated['allocation_date'] ?? now()->toDateString())
        );

        return (new PaymentResource($payment))->response();
    }
}
