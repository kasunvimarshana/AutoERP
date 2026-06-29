<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentLifecycleDimension;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\Payment;

final class PaymentDocumentLifecycleService
{
    public function __construct(private readonly PaymentLifecycleEventRecorder $events) {}

    public function submit(Payment $payment, int $expectedVersion, ?int $actorId = null): Payment
    {
        return $this->transition($payment, PaymentDocumentStatus::Submitted, $expectedVersion, $actorId, 'Payment submitted for approval.');
    }

    public function approve(Payment $payment, int $expectedVersion, ?int $actorId = null): Payment
    {
        return $this->transition($payment, PaymentDocumentStatus::Approved, $expectedVersion, $actorId, 'Payment approved.');
    }

    public function void(
        Payment $payment,
        int $expectedVersion,
        ?int $actorId = null,
        ?string $reason = null,
    ): Payment {
        $reason = trim((string) $reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Payment void reason is required.');
        }

        return DB::transaction(function () use ($payment, $expectedVersion, $actorId, $reason): Payment {
            $locked = $this->lock($payment, $expectedVersion);
            $posting = $this->postingStatus($locked);
            if ($posting !== PaymentPostingStatus::NotPosted || $locked->finance_posting_reference !== null) {
                throw new InvalidArgumentException('Posted payments must be reversed, not voided.');
            }
            if ($locked->allocations()->where('status', AllocationStatus::Active->value)->exists()) {
                throw new InvalidArgumentException('Allocated payments must be reversed before they can be voided.');
            }

            return $this->persistTransition($locked, PaymentDocumentStatus::Voided, $actorId, $reason, [
                'voided_by' => $actorId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);
        });
    }

    public function transition(
        Payment $payment,
        PaymentDocumentStatus $to,
        int $expectedVersion,
        ?int $actorId = null,
        ?string $reason = null,
    ): Payment {
        return DB::transaction(function () use ($payment, $to, $expectedVersion, $actorId, $reason): Payment {
            $locked = $this->lock($payment, $expectedVersion);
            $updates = [];
            if ($to === PaymentDocumentStatus::Approved) {
                $updates['approved_by'] = $actorId;
                $updates['approved_at'] = now();
            }

            return $this->persistTransition($locked, $to, $actorId, $reason, $updates);
        });
    }

    private function persistTransition(
        Payment $payment,
        PaymentDocumentStatus $to,
        ?int $actorId,
        ?string $reason,
        array $updates = [],
    ): Payment {
        $from = $this->documentStatus($payment);
        $this->assertCanTransition($from, $to);

        $payment->forceFill([
            ...$updates,
            'document_status' => $to->value,
            'row_version' => (int) $payment->row_version + 1,
        ])->save();
        $payment = $payment->refresh();
        $this->events->record($payment, PaymentLifecycleDimension::Document, $from, $to, $actorId, $reason);

        return $payment->refresh();
    }

    private function assertCanTransition(PaymentDocumentStatus $from, PaymentDocumentStatus $to): void
    {
        $allowed = match ($from) {
            PaymentDocumentStatus::Draft => [PaymentDocumentStatus::Submitted, PaymentDocumentStatus::Voided],
            PaymentDocumentStatus::Submitted => [PaymentDocumentStatus::Approved, PaymentDocumentStatus::Rejected, PaymentDocumentStatus::Voided],
            PaymentDocumentStatus::Rejected => [PaymentDocumentStatus::Submitted, PaymentDocumentStatus::Voided],
            PaymentDocumentStatus::Approved => [PaymentDocumentStatus::Voided],
            PaymentDocumentStatus::Voided, PaymentDocumentStatus::Reversed => [],
        };

        if (! in_array($to, $allowed, true)) {
            throw new InvalidArgumentException(sprintf(
                'Payment document cannot transition from %s to %s.',
                $from->value,
                $to->value,
            ));
        }
    }

    private function lock(Payment $payment, int $expectedVersion): Payment
    {
        if ($expectedVersion < 1) {
            throw new InvalidArgumentException('Expected payment version must be positive.');
        }

        $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
        if ((int) $locked->row_version !== $expectedVersion) {
            throw new InvalidArgumentException('Payment was changed by another request. Reload it before performing this action.');
        }

        return $locked;
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
}
