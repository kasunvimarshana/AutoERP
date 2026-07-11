<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;

final class PaymentAllocationReversalService
{
    public function __construct(
        private readonly PaymentAllocationStateService $allocationStates,
        private readonly PaymentBalanceSynchronizer $balances,
        private readonly InvoiceSettlementServiceInterface $invoiceSettlements,
    ) {}

    public function reverseForInvoice(
        Payment $payment,
        int $invoiceId,
        int $expectedPaymentVersion,
        string $reason,
        ?int $actorId = null,
    ): Payment {
        return DB::transaction(function () use (
            $payment,
            $invoiceId,
            $expectedPaymentVersion,
            $reason,
            $actorId,
        ): Payment {
            $reason = trim($reason);
            if ($reason === '') {
                throw new InvalidArgumentException('Payment allocation reversal reason is required.');
            }

            $payment = Payment::query()
                ->with(['lines', 'allocations'])
                ->lockForUpdate()
                ->findOrFail($payment->getKey());
            if ($expectedPaymentVersion < 1 || (int) $payment->row_version !== $expectedPaymentVersion) {
                throw new InvalidArgumentException('Payment was changed by another request. Reload it before reversing the allocation.');
            }
            $this->allocationStates->assertAllocatable($payment);

            $allocation = $payment->allocations()
                ->where('invoice_id', $invoiceId)
                ->where('status', AllocationStatus::Active->value)
                ->lockForUpdate()
                ->first();
            if (! $allocation instanceof PaymentAllocation) {
                throw new InvalidArgumentException('Active payment allocation was not found for the selected invoice.');
            }

            $this->invoiceSettlements->reversePaymentAllocation(
                $invoiceId,
                (string) $allocation->allocated_amount,
            );
            $allocation->forceFill([
                'status' => AllocationStatus::Reversed->value,
                'row_version' => (int) $allocation->row_version + 1,
                'metadata' => array_merge($allocation->metadata ?? [], [
                    'reversal' => [
                        'reason' => $reason,
                        'reversed_at' => now()->toISOString(),
                        'reversed_by' => $actorId,
                    ],
                ]),
            ])->save();

            return $this->balances->sync(
                $payment->refresh(),
                'Payment allocation reversed: '.$reason,
                $actorId,
            )->loadMissing([
                'lines',
                'allocations',
                'unappliedBalance',
                'lifecycleEvents',
            ]);
        });
    }
}
