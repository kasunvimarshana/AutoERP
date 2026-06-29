<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Payment\Models\Payment;

final class PaymentBalanceSynchronizer
{
    public function __construct(
        private readonly PaymentCalculationService $calculations,
        private readonly PaymentAllocationStateService $allocationStates,
        private readonly PaymentUnappliedBalanceService $unappliedBalances,
    ) {}

    public function sync(Payment $payment, ?string $reason = null, ?int $actorId = null): Payment
    {
        $calculation = $this->calculations->recalculate($payment);
        $payment->forceFill([
            'total_amount' => $calculation->totalAmount,
            'allocated_amount' => $calculation->allocatedAmount,
            'unapplied_amount' => $calculation->unappliedAmount,
            'refunded_amount' => $calculation->refundedAmount,
        ])->save();
        $payment = $this->allocationStates->sync(
            $payment->refresh(),
            $calculation->totalAmount,
            $calculation->allocatedAmount,
            $actorId,
            $reason,
        );
        $this->unappliedBalances->sync($payment);

        return $payment->refresh();
    }
}
