<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Payment\DTOs\PaymentBalanceResult;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Enums\UnappliedBalanceStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentUnappliedBalance;

final class PaymentUnappliedBalanceService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function sync(Payment $payment): ?PaymentUnappliedBalance
    {
        $originalAmount = $this->math->normalize((string) $payment->total_amount);
        $allocatedAmount = $this->math->normalize((string) $payment->allocated_amount);
        $refundedAmount = $this->math->normalize((string) $payment->refunded_amount);
        $remainingAmount = $this->math->normalize((string) $payment->unapplied_amount);

        $balance = PaymentUnappliedBalance::query()->firstOrNew([
            'payment_id' => $payment->getKey(),
        ]);

        if (! $balance->exists && $this->math->isZero($remainingAmount) && $this->math->isZero($refundedAmount)) {
            return null;
        }

        $balance->forceFill([
            'tenant_id' => $payment->tenant_id,
            'organization_unit_id' => $payment->organization_unit_id,
            'balance_type' => $this->balanceType($payment, $allocatedAmount),
            'party_type' => $payment->party_type,
            'party_id' => $payment->party_id,
            'source_type' => $payment->source_type,
            'source_id' => $payment->source_id,
            'allocation_status' => $this->allocationStatus($remainingAmount),
            'original_amount' => $originalAmount,
            'allocated_amount' => $allocatedAmount,
            'refunded_amount' => $refundedAmount,
            'remaining_amount' => $remainingAmount,
            'status' => $this->statusForAmounts($originalAmount, $allocatedAmount, $refundedAmount, $remainingAmount)->value,
            'metadata' => $payment->metadata,
        ])->save();

        return $balance->refresh();
    }

    public function result(PaymentUnappliedBalance $balance): PaymentBalanceResult
    {
        return new PaymentBalanceResult(
            originalAmount: (string) $balance->original_amount,
            allocatedAmount: (string) $balance->allocated_amount,
            refundedAmount: (string) $balance->refunded_amount,
            remainingAmount: (string) $balance->remaining_amount,
            status: $balance->status instanceof UnappliedBalanceStatus
                ? $balance->status
                : UnappliedBalanceStatus::from((string) $balance->status),
        );
    }

    public function statusForAmounts(
        string $originalAmount,
        string $allocatedAmount,
        string $refundedAmount,
        string $remainingAmount,
    ): UnappliedBalanceStatus {
        if ($this->math->isZero($remainingAmount)) {
            if (! $this->math->isZero($refundedAmount)) {
                return UnappliedBalanceStatus::Refunded;
            }

            return UnappliedBalanceStatus::FullyApplied;
        }

        if (! $this->math->isZero($allocatedAmount) || ! $this->math->isZero($refundedAmount)) {
            return UnappliedBalanceStatus::PartiallyApplied;
        }

        return UnappliedBalanceStatus::Available;
    }

    private function balanceType(Payment $payment, string $allocatedAmount): string
    {
        $sourceType = strtolower((string) $payment->source_type);
        if (str_contains($sourceType, 'deposit')) {
            return 'deposit';
        }

        $paymentType = $payment->payment_type instanceof PaymentType
            ? $payment->payment_type
            : PaymentType::from((string) $payment->payment_type);

        if ($paymentType === PaymentType::Advance) {
            return 'advance';
        }

        if (! $this->math->isZero($allocatedAmount)) {
            return 'overpayment';
        }

        return 'credit';
    }

    private function allocationStatus(string $remainingAmount): string
    {
        return $this->math->isZero($remainingAmount) ? 'fully_allocated' : 'unapplied';
    }
}
