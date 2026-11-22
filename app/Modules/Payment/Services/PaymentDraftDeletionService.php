<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentLifecycleDimension;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\Payment;

final class PaymentDraftDeletionService
{
    public function __construct(
        private readonly PaymentLifecycleEventRecorder $events,
    ) {}

    public function delete(Payment $payment, int $expectedVersion, ?int $actorId = null, ?string $reason = null): void
    {
        DB::transaction(function () use ($payment, $expectedVersion, $actorId, $reason): void {
            $locked = Payment::query()
                ->lockForUpdate()
                ->with(['lines', 'allocations', 'unappliedBalance'])
                ->findOrFail($payment->getKey());

            if ($expectedVersion < 1 || (int) $locked->row_version !== $expectedVersion) {
                throw new InvalidArgumentException('Payment was changed by another request. Reload it before deleting.');
            }

            $docStatus = $locked->document_status instanceof PaymentDocumentStatus
                ? $locked->document_status
                : PaymentDocumentStatus::from((string) $locked->document_status);

            if ($docStatus !== PaymentDocumentStatus::Draft) {
                throw new InvalidArgumentException('Only draft payments can be deleted.');
            }

            $postStatus = $locked->posting_status instanceof PaymentPostingStatus
                ? $locked->posting_status
                : PaymentPostingStatus::from((string) $locked->posting_status);

            if ($postStatus !== PaymentPostingStatus::NotPosted) {
                throw new InvalidArgumentException('Only unposted payments can be deleted.');
            }

            // Verify no active allocations
            $hasActiveAllocations = $locked->allocations->contains(
                fn ($allocation): bool => $allocation->status === AllocationStatus::Active->value
            );
            if ($hasActiveAllocations) {
                throw new InvalidArgumentException('Cannot delete a payment with active invoice allocations.');
            }

            // Record deletion in lifecycle audit events
            $this->events->record(
                $locked,
                PaymentLifecycleDimension::Document,
                $docStatus,
                'deleted',
                $actorId,
                $reason ?? 'Draft payment deleted.',
            );

            // Delete pending allocations
            $locked->allocations()->where('status', AllocationStatus::Pending->value)->delete();

            // Delete payment lines
            $locked->lines()->delete();

            // Delete unapplied balance record if exists
            $locked->unappliedBalance()->delete();

            // Soft-delete the draft payment
            $locked->delete();
        });
    }
}
