<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentLifecycleDimension;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\Payment;

final class PaymentAllocationStateService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentLifecycleEventRecorder $events,
    ) {}

    public function assertAllocatable(Payment $payment): void
    {
        $document = $payment->document_status instanceof PaymentDocumentStatus
            ? $payment->document_status
            : PaymentDocumentStatus::from((string) $payment->document_status);
        $posting = $payment->posting_status instanceof PaymentPostingStatus
            ? $payment->posting_status
            : PaymentPostingStatus::from((string) $payment->posting_status);

        if ($document !== PaymentDocumentStatus::Approved || $posting !== PaymentPostingStatus::Posted) {
            throw new InvalidArgumentException('Only approved and posted payments can be allocated.');
        }
    }

    public function sync(
        Payment $payment,
        string $totalAmount,
        string $allocatedAmount,
        ?int $actorId = null,
        ?string $reason = null,
    ): Payment {
        $from = $payment->allocation_status instanceof PaymentAllocationState
            ? $payment->allocation_status
            : PaymentAllocationState::from((string) $payment->allocation_status);
        $to = $this->forAmounts($totalAmount, $allocatedAmount);
        if ($from === $to) {
            return $payment;
        }

        $payment->forceFill([
            'allocation_status' => $to->value,
            'row_version' => (int) $payment->row_version + 1,
        ])->save();
        $payment = $payment->refresh();
        $this->events->record($payment, PaymentLifecycleDimension::Allocation, $from, $to, $actorId, $reason);

        return $payment->refresh();
    }

    public function forAmounts(string $totalAmount, string $allocatedAmount): PaymentAllocationState
    {
        if ($this->math->isZero($allocatedAmount)) {
            return PaymentAllocationState::Unallocated;
        }
        if ($this->math->compare($allocatedAmount, $totalAmount) < 0) {
            return PaymentAllocationState::PartiallyAllocated;
        }

        return PaymentAllocationState::FullyAllocated;
    }
}
