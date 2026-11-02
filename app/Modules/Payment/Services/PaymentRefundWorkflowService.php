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
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Models\PaymentRefund;
use Modules\Payment\Validators\PaymentValidationService;

final class PaymentRefundWorkflowService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentCreationService $payments,
        private readonly PaymentDocumentLifecycleService $lifecycle,
        private readonly PaymentPostingService $posting,
        private readonly PaymentValidationService $validator,
        private readonly PaymentBalanceSynchronizer $balances,
    ) {}

    public function refund(PaymentRefundData $data): PaymentRefund
    {
        return DB::transaction(function () use ($data): PaymentRefund {
            $original = Payment::query()->with(['lines', 'refunds'])->lockForUpdate()->findOrFail($data->paymentId);
            $this->assertVersion($original, $data->expectedVersion);
            if ($this->documentStatus($original) !== PaymentDocumentStatus::Approved
                || $this->postingStatus($original) !== PaymentPostingStatus::Posted) {
                throw new InvalidArgumentException('Only approved and posted payments can be refunded.');
            }
            $this->validator->assertPositive($data->amount, 'Refund amount');
            if ($this->math->compare($data->amount, (string) $original->unapplied_amount) > 0) {
                throw new InvalidArgumentException('Refund cannot exceed the payment unapplied amount.');
            }

            $paymentMethodId = $data->paymentMethodId ?? $original->lines->first()?->payment_method_id;
            $method = $paymentMethodId === null ? null : PaymentMethod::query()->find($paymentMethodId);
            $hasInstrumentDetails = trim((string) $data->instrumentNumber) !== ''
                || trim((string) $data->externalBankName) !== ''
                || trim((string) $data->instrumentDate) !== '';
            $refundDirection = $this->oppositeDirection($original);
            $this->validator->validatePaymentMethod(
                $method,
                (int) $original->tenant_id,
                $original->organization_unit_id,
                $refundDirection,
                $data->referenceNumber,
                $hasInstrumentDetails,
            );

            $refundPayment = $this->payments->create(new CreatePaymentData(
                tenantId: (int) $original->tenant_id,
                paymentType: PaymentType::Refund,
                direction: $refundDirection,
                paymentDate: $data->refundDate,
                organizationUnitId: $original->organization_unit_id,
                partyType: $original->party_type,
                partyId: $original->party_id,
                sourceType: 'payment_refund',
                sourceId: (int) $original->getKey(),
                originalPaymentId: (int) $original->getKey(),
                currencyId: $original->currency_id,
                exchangeRate: (string) $original->exchange_rate,
                referenceNumber: $data->referenceNumber,
                notes: $data->reason,
                createdBy: $data->refundedBy,
                lines: [new PaymentLineData(
                    amount: $data->amount,
                    paymentMethodId: $paymentMethodId,
                    referenceNumber: $data->referenceNumber,
                    instrumentDirection: $refundDirection === PaymentDirection::Inbound ? 'received' : 'issued',
                    externalBankName: $data->externalBankName,
                    externalBankBranch: $data->externalBankBranch,
                    instrumentNumber: $data->instrumentNumber,
                    instrumentDate: $data->instrumentDate,
                )],
                payeeName: $original->payee_name,
                metadata: ['original_payment_id' => (int) $original->getKey()],
            ));

            $refundPayment = $this->lifecycle->submit($refundPayment, (int) $refundPayment->row_version, $data->refundedBy);
            $refundPayment = $this->lifecycle->approve($refundPayment, (int) $refundPayment->row_version, $data->refundedBy);
            $refundPayment = $this->posting->post($refundPayment, (int) $refundPayment->row_version, $data->refundedBy);

            $refund = PaymentRefund::query()->create([
                'tenant_id' => $original->tenant_id,
                'organization_unit_id' => $original->organization_unit_id,
                'payment_id' => $original->getKey(),
                'refund_payment_id' => $refundPayment->getKey(),
                'refund_number' => $refundPayment->payment_number,
                'refund_date' => $data->refundDate,
                'amount' => $this->math->normalize($data->amount),
                'reason' => $data->reason,
                'refunded_by' => $data->refundedBy,
            ]);

            $original->forceFill(['row_version' => (int) $original->row_version + 1])->save();
            $this->balances->sync($original->refresh(), 'Payment refund recorded.', $data->refundedBy);

            return $refund->refresh()->load('refundPayment');
        });
    }

    private function assertVersion(Payment $payment, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $payment->row_version !== $expectedVersion) {
            throw new InvalidArgumentException('Payment was changed by another request. Reload it before refunding.');
        }
    }

    private function documentStatus(Payment $payment): PaymentDocumentStatus
    {
        return $payment->document_status instanceof PaymentDocumentStatus
            ? $payment->document_status
            : PaymentDocumentStatus::from((string) $payment->document_status);
    }

    private function postingStatus(Payment $payment): PaymentPostingStatus
    {
        return $payment->posting_status instanceof PaymentPostingStatus
            ? $payment->posting_status
            : PaymentPostingStatus::from((string) $payment->posting_status);
    }

    private function oppositeDirection(Payment $payment): PaymentDirection
    {
        $direction = $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::from((string) $payment->direction);

        return $direction === PaymentDirection::Inbound ? PaymentDirection::Outbound : PaymentDirection::Inbound;
    }
}
