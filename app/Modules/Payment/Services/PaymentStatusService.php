<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use InvalidArgumentException;
use Modules\Invoice\Services\DecimalMath;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

final class PaymentStatusService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @return array<string, list<string>>
     */
    private function transitions(): array
    {
        return [
            PaymentStatus::Draft->value => [
                PaymentStatus::Approved->value,
                PaymentStatus::Posted->value,
                PaymentStatus::Cancelled->value,
                PaymentStatus::Void->value,
            ],
            PaymentStatus::Approved->value => [
                PaymentStatus::Posted->value,
                PaymentStatus::Cancelled->value,
                PaymentStatus::Void->value,
            ],
            PaymentStatus::Posted->value => [
                PaymentStatus::PartiallyAllocated->value,
                PaymentStatus::Allocated->value,
                PaymentStatus::Reversed->value,
                PaymentStatus::Cancelled->value,
                PaymentStatus::Void->value,
            ],
            PaymentStatus::PartiallyAllocated->value => [
                PaymentStatus::Allocated->value,
                PaymentStatus::Reversed->value,
                PaymentStatus::Void->value,
            ],
            PaymentStatus::Allocated->value => [
                PaymentStatus::Reversed->value,
                PaymentStatus::Void->value,
            ],
            PaymentStatus::Void->value => [],
            PaymentStatus::Reversed->value => [],
            PaymentStatus::Cancelled->value => [],
        ];
    }

    public function assertCanTransition(PaymentStatus|string $from, PaymentStatus|string $to): void
    {
        $fromValue = $from instanceof PaymentStatus ? $from->value : $from;
        $toValue = $to instanceof PaymentStatus ? $to->value : $to;

        if (! in_array($toValue, $this->transitions()[$fromValue] ?? [], true)) {
            throw new InvalidArgumentException(sprintf('Payment status cannot transition from %s to %s.', $fromValue, $toValue));
        }
    }

    public function assertAllocatable(Payment $payment): void
    {
        $status = $payment->status instanceof PaymentStatus
            ? $payment->status
            : PaymentStatus::from((string) $payment->status);

        if (in_array($status, [PaymentStatus::Cancelled, PaymentStatus::Void, PaymentStatus::Reversed], true)) {
            throw new InvalidArgumentException('Cancelled, void, or reversed payments cannot be allocated.');
        }
    }

    public function statusForAmounts(string $totalAmount, string $allocatedAmount): PaymentStatus
    {
        if ($this->math->isZero($allocatedAmount)) {
            return PaymentStatus::Posted;
        }

        if ($this->math->compare($allocatedAmount, $totalAmount) < 0) {
            return PaymentStatus::PartiallyAllocated;
        }

        return PaymentStatus::Allocated;
    }

    public function applyCalculatedStatus(Payment $payment, string $totalAmount, string $allocatedAmount): Payment
    {
        $payment->forceFill([
            'status' => $this->statusForAmounts($totalAmount, $allocatedAmount)->value,
            'posted_at' => $payment->posted_at ?? now(),
        ])->save();

        return $payment->refresh();
    }
}
