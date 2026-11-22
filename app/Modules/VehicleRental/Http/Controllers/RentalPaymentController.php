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
        $agreementKind = AgreementKind::from($kind);
        $validated = $request->validate([
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'integer', 'min:1'],
            'allocations.*.amount' => ['required', 'regex:'.AgreementFields::DECIMAL_PATTERN, 'gt:0'],
            'allocation_date' => ['sometimes', 'date_format:'.AgreementFields::DATE_FORMAT],
        ]);

        $allocationDate = (string) ($validated['allocation_date'] ?? now()->toDateString());

        $updatedPayment = $service->allocateUnappliedFunds(
            $request->context(),
            $agreementKind,
            $agreement,
            $payment,
            $validated['allocations'],
            $allocationDate
        );

        return (new PaymentResource($updatedPayment))->response();
    }

    public function reverse(AgreementRequest $request, string $kind, int $agreement, int $payment, RentalPaymentService $service): JsonResponse
    {
        $agreementKind = AgreementKind::from($kind);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'reversal_date' => ['sometimes', 'date_format:'.AgreementFields::DATE_FORMAT],
        ]);

        $reversalDate = (string) ($validated['reversal_date'] ?? now()->toDateString());

        $reversedPayment = $service->reversePayment(
            $request->context(),
            $agreementKind,
            $agreement,
            $payment,
            $validated['reason'],
            $reversalDate
        );

        return (new PaymentResource($reversedPayment))->response();
    }

    public function openDocuments(AgreementRequest $request, string $kind, int $partyId, RentalPaymentService $service): JsonResponse
    {
        $agreementKind = AgreementKind::from($kind);
        $rentalOnly = $request->boolean('rental_only', true);
        $result = $service->getPartyOpenDocuments($request->context(), $agreementKind, $partyId, $rentalOnly);

        return response()->json(['data' => $result]);
    }

    public function storePartyPayment(RentalPaymentRequest $request, string $kind, int $partyId, RentalPaymentService $service): JsonResponse
    {
        $agreementKind = AgreementKind::from($kind);
        $expectedVersion = (int) $request->validated('expected_version', 1);
        $input = $request->validated();
        $lines = $request->paymentLineData();
        $allocations = $request->allocationsInput();

        $payment = $service->recordMultiAgreementPayment(
            $request->context(),
            $agreementKind,
            $partyId,
            $expectedVersion,
            $input,
            $lines,
            $allocations
        );

        return (new PaymentResource($payment))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function allocatePartyPayment(AgreementRequest $request, string $kind, int $partyId, int $payment, RentalPaymentService $service): JsonResponse
    {
        $agreementKind = AgreementKind::from($kind);
        $validated = $request->validate([
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'integer', 'min:1'],
            'allocations.*.amount' => ['required', 'regex:'.AgreementFields::DECIMAL_PATTERN, 'gt:0'],
            'allocation_date' => ['sometimes', 'date_format:'.AgreementFields::DATE_FORMAT],
        ]);

        $allocationDate = (string) ($validated['allocation_date'] ?? now()->toDateString());

        $updatedPayment = $service->allocatePartyUnappliedFunds(
            $request->context(),
            $agreementKind,
            $partyId,
            $payment,
            $validated['allocations'],
            $allocationDate
        );

        return (new PaymentResource($updatedPayment))->response();
    }

    public function refund(AgreementRequest $request, string $kind, int $agreement, int $payment, RentalPaymentService $service): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'regex:'.AgreementFields::DECIMAL_PATTERN, 'gt:0'],
            'payment_method_id' => ['required', 'integer', 'min:1'],
            'refund_date' => ['sometimes', 'date_format:'.AgreementFields::DATE_FORMAT],
            'exchange_rate' => ['sometimes', 'regex:'.AgreementFields::DECIMAL_PATTERN, 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'external_bank_name' => ['nullable', 'string', 'max:100'],
            'external_bank_branch' => ['nullable', 'string', 'max:100'],
            'instrument_number' => ['nullable', 'string', 'max:100'],
            'instrument_date' => ['nullable', 'date_format:'.AgreementFields::DATE_FORMAT],
            'reason' => ['nullable', 'string', 'max:500'],
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $agreementKind = AgreementKind::from($kind);
        $refund = $service->refundPayment(
            $request->context(),
            $agreementKind,
            $agreement,
            $payment,
            $validated
        );

        return response()->json(['data' => $refund], Response::HTTP_CREATED);
    }
}
