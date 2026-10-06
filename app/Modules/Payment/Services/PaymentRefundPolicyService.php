<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;

final class PaymentRefundPolicyService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function originalForCreation(CreatePaymentData $data): Payment
    {
        if ($data->originalPaymentId === null) {
            throw new InvalidArgumentException('Refund payment requires the original payment.');
        }

        return $this->resolveOriginal(
            originalPaymentId: $data->originalPaymentId,
            tenantId: $data->tenantId,
            organizationUnitId: $data->organizationUnitId,
            partyType: $data->partyType,
            partyId: $data->partyId,
            currencyId: $data->currencyId,
            refundDirection: $data->direction,
        );
    }

    public function originalForPayment(Payment $payment): Payment
    {
        if ($payment->original_payment_id === null) {
            throw new InvalidArgumentException('Refund payment requires the original payment.');
        }

        return $this->resolveOriginal(
            originalPaymentId: (int) $payment->original_payment_id,
            tenantId: (int) $payment->tenant_id,
            organizationUnitId: $payment->organization_unit_id === null
                ? null
                : (int) $payment->organization_unit_id,
            partyType: $payment->party_type === null ? null : (string) $payment->party_type,
            partyId: $payment->party_id === null ? null : (int) $payment->party_id,
            currencyId: $payment->currency_id === null ? null : (int) $payment->currency_id,
            refundDirection: $this->direction($payment),
        );
    }

    public function reservedUnpostedAmount(Payment $payment): string
    {
        $amount = (string) $payment->refunds()
            ->whereHas('refundPayment', function (Builder $query): void {
                $query
                    ->whereNotIn('document_status', [
                        PaymentDocumentStatus::Voided->value,
                        PaymentDocumentStatus::Reversed->value,
                    ])
                    ->where('posting_status', '!=', PaymentPostingStatus::Posted->value);
            })
            ->sum('amount');

        return $this->math->normalize($amount);
    }

    public function availableUnappliedAmount(Payment $payment): string
    {
        $available = $this->math->sub(
            (string) $payment->unapplied_amount,
            $this->reservedUnpostedAmount($payment),
        );
        if ($this->math->isNegative($available)) {
            throw new InvalidArgumentException('Payment refund reservations exceed the unapplied payment amount.');
        }

        return $this->math->normalize($available);
    }

    private function resolveOriginal(
        int $originalPaymentId,
        int $tenantId,
        ?int $organizationUnitId,
        ?string $partyType,
        ?int $partyId,
        ?int $currencyId,
        PaymentDirection $refundDirection,
    ): Payment {
        $original = Payment::query()->find($originalPaymentId);
        if (! $original instanceof Payment) {
            throw new InvalidArgumentException('Original payment was not found for refund.');
        }

        $originalType = $original->payment_type instanceof PaymentType
            ? $original->payment_type
            : PaymentType::from((string) $original->payment_type);
        if ($originalType === PaymentType::Refund) {
            throw new InvalidArgumentException('A refund payment cannot be refunded again.');
        }

        $documentStatus = $original->document_status instanceof PaymentDocumentStatus
            ? $original->document_status
            : PaymentDocumentStatus::from((string) $original->document_status);
        $postingStatus = $original->posting_status instanceof PaymentPostingStatus
            ? $original->posting_status
            : PaymentPostingStatus::from((string) $original->posting_status);
        if ($documentStatus !== PaymentDocumentStatus::Approved || $postingStatus !== PaymentPostingStatus::Posted) {
            throw new InvalidArgumentException('The original payment must be approved and posted before it can be refunded.');
        }

        if ((int) $original->tenant_id !== $tenantId
            || $this->nullableInt($original->organization_unit_id) !== $organizationUnitId
            || $this->nullableString($original->party_type) !== $partyType
            || $this->nullableInt($original->party_id) !== $partyId
            || $this->nullableInt($original->currency_id) !== $currencyId
        ) {
            throw new InvalidArgumentException('Refund payment scope, party, and currency must match the original payment.');
        }

        if ($this->direction($original) === $refundDirection) {
            throw new InvalidArgumentException('Refund payment direction must reverse the original payment direction.');
        }

        return $original;
    }

    private function direction(Payment $payment): PaymentDirection
    {
        return $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::from((string) $payment->direction);
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
