<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

final class PaymentStatusService
{
    public function __construct(private readonly DecimalMath $math) {}

    /** @return array<string, list<string>> */
    private function transitions(): array
    {
        return [
            PaymentStatus::Draft->value => [PaymentStatus::PendingApproval->value, PaymentStatus::Approved->value, PaymentStatus::Cancelled->value, PaymentStatus::Voided->value, PaymentStatus::Void->value],
            PaymentStatus::PendingApproval->value => [PaymentStatus::Approved->value, PaymentStatus::Cancelled->value, PaymentStatus::Voided->value, PaymentStatus::Void->value],
            PaymentStatus::Approved->value => [PaymentStatus::Cancelled->value, PaymentStatus::Voided->value, PaymentStatus::Void->value],
            PaymentStatus::Posted->value => [PaymentStatus::Reversed->value],
            PaymentStatus::Voided->value => [],
            PaymentStatus::Void->value => [],
            PaymentStatus::Reversed->value => [],
            PaymentStatus::Cancelled->value => [],
            PaymentStatus::PartiallyAllocated->value => [PaymentStatus::Reversed->value],
            PaymentStatus::Allocated->value => [PaymentStatus::Reversed->value],
            PaymentStatus::FullyAllocated->value => [PaymentStatus::Reversed->value],
            PaymentStatus::Refunded->value => [PaymentStatus::Reversed->value],
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
        $status = $this->status($payment);
        $posting = $payment->posting_status instanceof PaymentPostingStatus
            ? $payment->posting_status
            : PaymentPostingStatus::from((string) $payment->posting_status);

        if ($status !== PaymentStatus::Posted || $posting !== PaymentPostingStatus::Posted) {
            throw new InvalidArgumentException('Only posted payments can be allocated.');
        }
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
        unset($actorId, $reason, $refundedAmount);

        $payment->forceFill([
            'allocation_status' => $this->allocationStateForAmounts($totalAmount, $allocatedAmount)->value,
        ])->save();

        return $payment->refresh();
    }

    public function transition(Payment $payment, PaymentStatus $to, ?int $actorId = null, ?string $reason = null): Payment
    {
        if ($to === PaymentStatus::Posted) {
            throw new InvalidArgumentException('Use the payment posting service to post payments.');
        }

        $from = $this->status($payment);
        $this->assertCanTransition($from, $to);

        $updates = [
            'status' => $to->value,
            'document_status' => $this->documentStatusFor($to)->value,
        ];
        if ($to === PaymentStatus::Approved) {
            $updates['approved_by'] = $actorId;
            $updates['approved_at'] = now();
        }

        $payment->forceFill($updates)->save();
        $this->record($payment->refresh(), $from, $to, $actorId, $reason);

        return $payment->refresh();
    }

    public function markPosted(Payment $payment, ?int $actorId = null, ?string $reason = null): Payment
    {
        $from = $this->status($payment);
        $payment->forceFill([
            'status' => PaymentStatus::Posted->value,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'posting_status' => PaymentPostingStatus::Posted->value,
            'posted_at' => now(),
        ])->save();
        $this->record($payment->refresh(), $from, PaymentStatus::Posted, $actorId, $reason ?? 'Payment posted.');

        return $payment->refresh();
    }

    public function void(Payment $payment, ?int $voidedBy = null, ?string $reason = null): Payment
    {
        $posting = $payment->posting_status instanceof PaymentPostingStatus
            ? $payment->posting_status
            : PaymentPostingStatus::from((string) $payment->posting_status);
        if ($posting === PaymentPostingStatus::Posted || $payment->finance_journal_entry_id !== null) {
            throw new InvalidArgumentException('Posted payments must be reversed, not voided.');
        }
        if ($payment->allocations()->where('status', 'active')->exists()) {
            throw new InvalidArgumentException('Allocated payments must be reversed before they can be voided.');
        }

        $payment = $this->transition($payment, PaymentStatus::Voided, $voidedBy, $reason);
        $payment->forceFill([
            'voided_by' => $voidedBy,
            'voided_at' => now(),
            'void_reason' => $reason,
        ])->save();

        return $payment->refresh();
    }

    public function recordInitial(Payment $payment, ?int $actorId = null): void
    {
        $this->record($payment, null, $this->status($payment), $actorId, 'Payment created.');
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

    private function status(Payment $payment): PaymentStatus
    {
        return $payment->status instanceof PaymentStatus
            ? $payment->status
            : PaymentStatus::from((string) $payment->status);
    }

    private function documentStatusFor(PaymentStatus $status): PaymentDocumentStatus
    {
        return match ($status) {
            PaymentStatus::PendingApproval => PaymentDocumentStatus::Submitted,
            PaymentStatus::Approved, PaymentStatus::Posted => PaymentDocumentStatus::Approved,
            PaymentStatus::Voided, PaymentStatus::Void, PaymentStatus::Cancelled => PaymentDocumentStatus::Voided,
            PaymentStatus::Reversed => PaymentDocumentStatus::Reversed,
            default => PaymentDocumentStatus::Draft,
        };
    }
}
