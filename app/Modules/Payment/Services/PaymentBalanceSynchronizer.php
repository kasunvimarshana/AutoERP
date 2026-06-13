<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Payment\Models\Payment;

final class PaymentBalanceSynchronizer
{
    public function __construct(
        private readonly PaymentCalculationService $calculations,
        private readonly PaymentStatusService $statuses,
        private readonly PaymentUnappliedBalanceService $unappliedBalances,
    ) {}

    public function sync(Payment $payment, ?string $reason = null): Payment
    {
        $calculation = $this->calculations->recalculate($payment);

        $payment->forceFill([
            'total_amount' => $calculation->totalAmount,
            'allocated_amount' => $calculation->allocatedAmount,
            'unapplied_amount' => $calculation->unappliedAmount,
            'refunded_amount' => $calculation->refundedAmount,
        ])->save();

        $payment = $this->statuses->applyCalculatedStatus(
            $payment->refresh(),
            $calculation->totalAmount,
            $calculation->allocatedAmount,
            $calculation->refundedAmount,
            reason: $reason,
        );
        $this->unappliedBalances->sync($payment);

        return $payment->refresh();
    }
}
