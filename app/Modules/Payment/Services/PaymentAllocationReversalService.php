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
        private readonly PaymentAllocationFinanceService $allocationFinance,
    ) {}

    public function reverse(
        Payment $payment,
        int $allocationId,
        int $expectedPaymentVersion,
        int $expectedAllocationVersion,
        string $reversalDate,
        string $reason,
        ?int $actorId = null,
    ): Payment {
        return DB::transaction(function () use (
            $payment,
            $allocationId,
            $expectedPaymentVersion,
            $expectedAllocationVersion,
            $reversalDate,
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
                ->whereKey($allocationId)
                ->where('status', AllocationStatus::Active->value)
                ->lockForUpdate()
                ->first();
            if (! $allocation instanceof PaymentAllocation) {
                throw new InvalidArgumentException('Active payment allocation was not found.');
            }
            if ($expectedAllocationVersion < 1 || (int) $allocation->row_version !== $expectedAllocationVersion) {
                throw new InvalidArgumentException('Payment allocation was changed by another request. Reload it before reversing.');
            }

            $allocationDate = $allocation->allocation_date?->toDateString();
            if ($allocationDate !== null && $reversalDate < $allocationDate) {
                throw new InvalidArgumentException('Payment allocation reversal date cannot be before the allocation date.');
            }

            $this->allocationFinance->reverse(
                $payment,
                $allocation,
                $reversalDate,
                $reason,
                $actorId,
            );
            $this->invoiceSettlements->reversePaymentAllocation(
                (int) $allocation->invoice_id,
                (string) $allocation->allocated_amount,
            );
            $allocation->forceFill([
                'status' => AllocationStatus::Reversed->value,
                'active_identity_slot' => null,
                'row_version' => (int) $allocation->row_version + 1,
                'metadata' => array_merge($allocation->metadata ?? [], [
                    'reversal' => [
                        'reason' => $reason,
                        'reversal_date' => $reversalDate,
                        'reversed_at' => now()->toISOString(),
                        'reversed_by' => $actorId,
                    ],
                ]),
            ])->save();

            $payment = $this->balances->sync(
                $payment->refresh(),
                'Payment allocation reversed: '.$reason,
                $actorId,
            );
            $payment->forceFill([
                'row_version' => (int) $payment->row_version + 1,
            ])->save();

            return $payment->refresh()->loadMissing([
                'lines',
                'allocations',
                'unappliedBalance',
                'lifecycleEvents',
            ]);
        });
    }
}
