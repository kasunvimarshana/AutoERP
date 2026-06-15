<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
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
                PaymentStatus::PendingApproval->value,
                PaymentStatus::Approved->value,
                PaymentStatus::Posted->value,
                PaymentStatus::Cancelled->value,
                PaymentStatus::Void->value,
            ],
            PaymentStatus::PendingApproval->value => [
                PaymentStatus::Approved->value,
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
                PaymentStatus::FullyAllocated->value,
                PaymentStatus::Allocated->value,
                PaymentStatus::Refunded->value,
                PaymentStatus::Reversed->value,
            ],
            PaymentStatus::PartiallyAllocated->value => [
                PaymentStatus::FullyAllocated->value,
                PaymentStatus::Allocated->value,
                PaymentStatus::Refunded->value,
                PaymentStatus::Reversed->value,
            ],
            PaymentStatus::Allocated->value => [
                PaymentStatus::Reversed->value,
            ],
            PaymentStatus::FullyAllocated->value => [
                PaymentStatus::Reversed->value,
            ],
            PaymentStatus::Refunded->value => [],
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

        if (in_array($status, [PaymentStatus::Cancelled, PaymentStatus::Void, PaymentStatus::Reversed, PaymentStatus::Refunded], true)) {
            throw new InvalidArgumentException('Cancelled, void, refunded, or reversed payments cannot be allocated.');
        }
    }

    public function statusForAmounts(string $totalAmount, string $allocatedAmount, string $refundedAmount = '0.000000'): PaymentStatus
    {
        if (! $this->math->isZero($refundedAmount)
            && $this->math->compare($this->math->add($allocatedAmount, $refundedAmount), $totalAmount) >= 0) {
            return PaymentStatus::Refunded;
        }

        if ($this->math->isZero($allocatedAmount)) {
            return PaymentStatus::Posted;
        }

        if ($this->math->compare($allocatedAmount, $totalAmount) < 0) {
            return PaymentStatus::PartiallyAllocated;
        }

        return PaymentStatus::FullyAllocated;
    }

    public function allocationStateForAmounts(string $totalAmount, string $allocatedAmount): PaymentAllocationState
    {
        if ($this->math->isZero($allocatedAmount)) {
            return PaymentAllocationState::Unallocated;
        }

        if ($this->math->compare($allocatedAmount, $totalAmount) < 0) {
            return PaymentAllocationState::PartiallyAllocated;
        }

        return PaymentAllocationState::FullyAllocated;
    }

    public function applyCalculatedStatus(
        Payment $payment,
        string $totalAmount,
        string $allocatedAmount,
        string $refundedAmount = '0.000000',
        ?int $actorId = null,
        ?string $reason = null,
    ): Payment {
        $from = $payment->status instanceof PaymentStatus
            ? $payment->status
            : PaymentStatus::from((string) $payment->status);
        $to = $this->statusForAmounts($totalAmount, $allocatedAmount, $refundedAmount);

        $payment->forceFill([
            'status' => $to->value,
            'allocation_status' => $this->allocationStateForAmounts($totalAmount, $allocatedAmount)->value,
            'document_status' => $this->documentStatusFor($to)->value,
            'posting_status' => $this->postingStatusFor($to)->value,
            'posted_at' => $payment->posted_at ?? now(),
        ])->save();

        if ($from !== $to) {
            $this->record($payment->refresh(), $from, $to, $actorId, $reason);
        }

        return $payment->refresh();
    }

    public function transition(Payment $payment, PaymentStatus $to, ?int $actorId = null, ?string $reason = null): Payment
    {
        $from = $payment->status instanceof PaymentStatus
            ? $payment->status
            : PaymentStatus::from((string) $payment->status);
        $this->assertCanTransition($from, $to);

        $updates = [
            'status' => $to->value,
            'document_status' => $this->documentStatusFor($to)->value,
            'posting_status' => $this->postingStatusFor($to)->value,
        ];
        if ($to === PaymentStatus::Approved) {
            $updates['approved_by'] = $actorId;
            $updates['approved_at'] = now();
        }
        if ($to === PaymentStatus::Posted) {
            $updates['posted_at'] = now();
        }

        $payment->forceFill($updates)->save();
        $this->record($payment->refresh(), $from, $to, $actorId, $reason);

        return $payment->refresh();
    }

    public function void(Payment $payment, ?int $voidedBy = null, ?string $reason = null): Payment
    {
        if ($payment->allocations()->where('status', AllocationStatus::Active->value)->exists()) {
            throw new InvalidArgumentException('Allocated payments must be reversed before they can be voided.');
        }

        $payment = $this->transition($payment, PaymentStatus::Void, $voidedBy, $reason);
        $payment->forceFill([
            'voided_by' => $voidedBy,
            'voided_at' => now(),
            'void_reason' => $reason,
        ])->save();

        return $payment->refresh();
    }

    public function recordInitial(Payment $payment, ?int $actorId = null): void
    {
        $to = $payment->status instanceof PaymentStatus
            ? $payment->status
            : PaymentStatus::from((string) $payment->status);

        $this->record($payment, null, $to, $actorId, 'Payment created.');
    }

    public function record(
        Payment $payment,
        PaymentStatus|string|null $from,
        PaymentStatus|string $to,
        ?int $actorId = null,
        ?string $reason = null,
        ?array $metadata = null,
    ): void {
        $fromValue = $from instanceof PaymentStatus ? $from->value : $from;
        $toValue = $to instanceof PaymentStatus ? $to->value : $to;

        $payment->statusHistory()->create([
            'tenant_id' => $payment->tenant_id,
            'organization_unit_id' => $payment->organization_unit_id,
            'from_status' => $fromValue,
            'to_status' => $toValue,
            'reason' => $reason,
            'changed_by' => $actorId,
            'changed_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    private function documentStatusFor(PaymentStatus $status): PaymentDocumentStatus
    {
        return match ($status) {
            PaymentStatus::PendingApproval => PaymentDocumentStatus::Submitted,
            PaymentStatus::Approved,
            PaymentStatus::Posted,
            PaymentStatus::PartiallyAllocated,
            PaymentStatus::Allocated,
            PaymentStatus::FullyAllocated,
            PaymentStatus::Refunded => PaymentDocumentStatus::Approved,
            PaymentStatus::Void,
            PaymentStatus::Cancelled => PaymentDocumentStatus::Voided,
            PaymentStatus::Reversed => PaymentDocumentStatus::Reversed,
            default => PaymentDocumentStatus::Draft,
        };
    }

    private function postingStatusFor(PaymentStatus $status): PaymentPostingStatus
    {
        return match ($status) {
            PaymentStatus::Posted,
            PaymentStatus::PartiallyAllocated,
            PaymentStatus::Allocated,
            PaymentStatus::FullyAllocated,
            PaymentStatus::Refunded => PaymentPostingStatus::Posted,
            PaymentStatus::Reversed => PaymentPostingStatus::Reversed,
            default => PaymentPostingStatus::NotPosted,
        };
    }
}
