<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Payment\DTOs\PaymentBalanceResult;
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
            'original_amount' => $originalAmount,
            'allocated_amount' => $allocatedAmount,
            'refunded_amount' => $refundedAmount,
            'remaining_amount' => $remainingAmount,
            'status' => $this->statusForAmounts($originalAmount, $allocatedAmount, $refundedAmount, $remainingAmount)->value,
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
}
