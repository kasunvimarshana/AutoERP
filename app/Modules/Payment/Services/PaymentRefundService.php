<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\DTOs\PaymentRefundData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Models\PaymentRefund;
use Modules\Payment\Validators\PaymentValidationService;

final class PaymentRefundService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentValidationService $validator,
        private readonly PaymentStatusService $statuses,
        private readonly PaymentBalanceSynchronizer $balances,
        private readonly PaymentCreationService $payments,
        private readonly PaymentPostingService $posting,
    ) {}

    public function refund(PaymentRefundData $data): PaymentRefund
    {
        return DB::transaction(function () use ($data): PaymentRefund {
            $payment = Payment::query()->with(['lines.paymentMethod'])->lockForUpdate()->findOrFail($data->paymentId);
            $this->statuses->assertAllocatable($payment);
            $this->validator->assertPositive($data->amount, 'Payment refund amount');

            if (PaymentRefund::query()
                ->where('tenant_id', $payment->tenant_id)
                ->where('payment_id', $payment->getKey())
                ->where('refund_number', $data->refundNumber)
                ->exists()) {
                throw new InvalidArgumentException('Duplicate payment refund detected.');
            }

            if ($this->math->compare($data->amount, (string) $payment->unapplied_amount) > 0) {
                throw new InvalidArgumentException('Payment refund cannot exceed unapplied payment balance.');
            }

            $refundDirection = $this->oppositeDirection($payment);
            $sourceLine = $payment->lines->first();
            $paymentMethodId = $data->paymentMethodId ?? ($sourceLine?->payment_method_id === null ? null : (int) $sourceLine->payment_method_id);
            if ($paymentMethodId === null) {
                throw new InvalidArgumentException('Payment refund requires a payment method.');
            }

            $this->validator->validatePaymentMethod(
                PaymentMethod::query()->find($paymentMethodId),
                (int) $payment->tenant_id,
                $payment->organization_unit_id,
                $refundDirection,
                null,
                null,
            );

            $refundPayment = $this->payments->create(new CreatePaymentData(
                tenantId: (int) $payment->tenant_id,
                paymentType: PaymentType::Refund,
                direction: $refundDirection,
                paymentDate: $data->refundDate,
                organizationUnitId: $payment->organization_unit_id,
                paymentNumber: $data->refundNumber,
                partyType: $data->partyType ?? $payment->party_type,
                partyId: $data->partyId ?? ($payment->party_id === null ? null : (int) $payment->party_id),
                sourceType: 'payment',
                sourceId: (int) $payment->getKey(),
                currencyId: $payment->currency_id,
                exchangeRate: (string) $payment->exchange_rate,
                referenceNumber: $data->refundNumber,
                status: PaymentStatus::Approved,
                notes: $data->reason,
                lines: [new PaymentLineData(
                    amount: $data->amount,
                    paymentMethodId: $paymentMethodId,
                    referenceNumber: $data->refundNumber,
                    internalBankAccountId: $sourceLine?->internal_bank_account_id === null ? $payment->bank_account_id : (int) $sourceLine->internal_bank_account_id,
                    instrumentDirection: $refundDirection === PaymentDirection::Inbound ? 'received' : 'issued',
                )],
                bankAccountId: $payment->bank_account_id,
                payeeName: $payment->payee_name,
                metadata: array_merge($data->metadata ?? [], [
                    'original_payment_id' => $payment->getKey(),
                    'refund_reason' => $data->reason,
                ]),
            ));
            $refundPayment->forceFill(['original_payment_id' => $payment->getKey()])->save();
            $refundPayment = $this->posting->post($refundPayment->refresh());

            $refund = PaymentRefund::query()->create([
                'tenant_id' => $payment->tenant_id,
                'organization_unit_id' => $payment->organization_unit_id,
                'payment_id' => $payment->getKey(),
                'refund_payment_id' => $refundPayment->getKey(),
                'refund_number' => $data->refundNumber,
                'refund_date' => $data->refundDate,
                'party_type' => $data->partyType ?? $payment->party_type,
                'party_id' => $data->partyId ?? $payment->party_id,
                'payment_method_id' => $paymentMethodId,
                'amount' => $this->math->normalize($data->amount),
                'reason' => $data->reason,
                'status' => $data->status,
                'metadata' => $data->metadata,
            ]);

            $this->balances->sync($payment->refresh(), 'Payment refund recalculated.');

            return $refund->refresh()->load('refundPayment');
        });
    }

    private function oppositeDirection(Payment $payment): PaymentDirection
    {
        $direction = $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::from((string) $payment->direction);

        return $direction === PaymentDirection::Inbound ? PaymentDirection::Outbound : PaymentDirection::Inbound;
    }
}
