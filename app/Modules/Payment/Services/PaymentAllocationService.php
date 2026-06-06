<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Validators\PaymentValidationService;

final class PaymentAllocationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentValidationService $validator,
        private readonly PaymentCalculationService $calculations,
        private readonly PaymentStatusService $statuses,
        private readonly PaymentUnappliedBalanceService $unappliedBalances,
        private readonly InvoiceBalanceProviderInterface $invoiceBalances,
        private readonly InvoiceSettlementServiceInterface $invoiceSettlements,
    ) {}

    /**
     * @param  list<PaymentAllocationData>  $allocations
     */
    public function allocate(Payment $payment, array $allocations): Payment
    {
        return DB::transaction(function () use ($payment, $allocations): Payment {
            $payment = Payment::query()->lockForUpdate()->with(['lines', 'allocations'])->findOrFail($payment->getKey());
            $this->statuses->assertAllocatable($payment);

            foreach ($allocations as $allocation) {
                if (! $allocation instanceof PaymentAllocationData) {
                    throw new InvalidArgumentException('Payment allocations must be PaymentAllocationData instances.');
                }

                $this->allocateOne($payment, $allocation);
                $payment = $this->syncPaymentAmounts($payment->refresh());
            }

            return $payment->load(['lines', 'allocations', 'unappliedBalance']);
        });
    }

    private function allocateOne(Payment $payment, PaymentAllocationData $allocation): PaymentAllocation
    {
        $invoiceBalance = $this->invoiceBalances->validatePayableState($allocation->invoiceId);
        $this->validator->validateInvoiceAllocation($payment, $invoiceBalance, $allocation);

        $availableAmount = $this->availableAmount($payment);
        if ($this->math->compare($allocation->allocatedAmount, $availableAmount) > 0) {
            throw new InvalidArgumentException('Payment allocation cannot exceed available payment amount.');
        }

        $invoiceBalanceBefore = $this->math->normalize($invoiceBalance->remainingAmount);
        if (! $allocation->allowOverpayment && $this->math->compare($allocation->allocatedAmount, $invoiceBalanceBefore) > 0) {
            throw new InvalidArgumentException('Payment allocation cannot exceed invoice remaining balance.');
        }

        $previouslyAllocated = $this->math->normalize((string) PaymentAllocation::query()
            ->where('payment_id', $payment->getKey())
            ->where('invoice_id', $allocation->invoiceId)
            ->where('status', AllocationStatus::Active->value)
            ->sum('allocated_amount'));

        $settlement = $this->invoiceSettlements->applyPaymentAllocation(
            $allocation->invoiceId,
            $allocation->allocatedAmount,
            $allocation->allowOverpayment,
        );

        return PaymentAllocation::query()->create([
            'tenant_id' => $payment->tenant_id,
            'organization_unit_id' => $payment->organization_unit_id,
            'payment_id' => $payment->getKey(),
            'invoice_id' => $allocation->invoiceId,
            'invoice_total' => $invoiceBalance->totalAmount,
            'invoice_balance_before' => $invoiceBalanceBefore,
            'previously_allocated_amount' => $previouslyAllocated,
            'allocated_amount' => $this->math->normalize($allocation->allocatedAmount),
            'invoice_balance_after' => $settlement->balanceAfter,
            'allocation_date' => $allocation->allocationDate,
            'status' => AllocationStatus::Active->value,
        ]);
    }

    public function syncPaymentAmounts(Payment $payment): Payment
    {
        $calculation = $this->calculations->recalculate($payment);

        $payment->forceFill([
            'total_amount' => $calculation->totalAmount,
            'allocated_amount' => $calculation->allocatedAmount,
            'unapplied_amount' => $calculation->unappliedAmount,
            'refunded_amount' => $calculation->refundedAmount,
        ])->save();

        $payment = $this->statuses->applyCalculatedStatus($payment->refresh(), $calculation->totalAmount, $calculation->allocatedAmount);
        $this->unappliedBalances->sync($payment);

        return $payment->refresh();
    }

    private function availableAmount(Payment $payment): string
    {
        return $this->math->normalize((string) $payment->unapplied_amount);
    }
}
