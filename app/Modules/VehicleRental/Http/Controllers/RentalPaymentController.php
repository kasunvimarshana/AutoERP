<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Http\Requests\RentalPaymentRequest;
use Modules\VehicleRental\Services\RentalPaymentService;
use Symfony\Component\HttpFoundation\Response;

final class RentalPaymentController
{
    public function index(AgreementRequest $request, string $kind, int $agreement, RentalPaymentService $service): JsonResponse
    {
        $agreementKind = AgreementKind::from($kind);
        $payments = $service->listAgreementPayments($request->context(), $agreement, $agreementKind);

        return response()->json(['data' => $payments]);
    }

    public function store(RentalPaymentRequest $request, string $kind, int $agreement, RentalPaymentService $service): JsonResponse
    {
        $agreementKind = AgreementKind::from($kind);
        $expectedVersion = (int) $request->validated('expected_version');
        $input = $request->validated();
        $lines = $request->paymentLineData();
        $allocations = $request->allocationsInput();

        if ($agreementKind === AgreementKind::Customer) {
            $payment = $service->recordCustomerReceipt(
                $request->context(),
                $agreement,
                $expectedVersion,
                $input,
                $lines,
                $allocations
            );
        } else {
            $payment = $service->recordOwnerDisbursement(
                $request->context(),
                $agreement,
                $expectedVersion,
                $input,
                $lines,
                $allocations
            );
        }

        return (new PaymentResource($payment))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function allocate(AgreementRequest $request, string $kind, int $agreement, int $payment, RentalPaymentService $service): JsonResponse
    {
        $validated = $request->validate([
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'integer', 'min:1'],
            'allocations.*.amount' => ['required', 'regex:'.AgreementFields::DECIMAL_PATTERN, 'gt:0'],
            'allocation_date' => ['sometimes', 'date_format:'.AgreementFields::DATE_FORMAT],
        ]);

        $allocationDate = (string) ($validated['allocation_date'] ?? now()->toDateString());

        $updatedPayment = $service->allocateUnappliedFunds(
            $request->context(),
            $agreement,
            $payment,
            $validated['allocations'],
            $allocationDate
        );

        return (new PaymentResource($updatedPayment))->response();
    }

    public function reverse(AgreementRequest $request, string $kind, int $agreement, int $payment, RentalPaymentService $service): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'reversal_date' => ['sometimes', 'date_format:'.AgreementFields::DATE_FORMAT],
        ]);

        $reversalDate = (string) ($validated['reversal_date'] ?? now()->toDateString());

        $reversedPayment = $service->reversePayment(
            $request->context(),
            $agreement,
            $payment,
            $validated['reason'],
            $reversalDate
        );

        return (new PaymentResource($reversedPayment))->response();
    }
}
