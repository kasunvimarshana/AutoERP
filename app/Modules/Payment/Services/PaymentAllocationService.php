<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceBalance;
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

    /**
     * @param  list<PaymentAllocationData>  $allocations
     */
    public function allocateByMethod(
        Payment $payment,
        string $method,
        string $allocationDate,
        array $allocations = [],
        ?string $amount = null,
    ): Payment {
        $method = strtolower(trim($method));

        if (in_array($method, ['manual', 'specific_invoice'], true)) {
            return $this->allocate($payment, array_map(
                static fn (PaymentAllocationData $allocation): PaymentAllocationData => new PaymentAllocationData(
                    invoiceId: $allocation->invoiceId,
                    allocatedAmount: $allocation->allocatedAmount,
                    allocationDate: $allocation->allocationDate,
                    allowOverpayment: $allocation->allowOverpayment,
                    allocationMethod: $method,
                    metadata: $allocation->metadata,
                ),
                $allocations,
            ));
        }

        if ($method !== 'fifo') {
            throw new InvalidArgumentException('Unsupported payment allocation method.');
        }

        return $this->allocate($payment, $this->fifoAllocations($payment, $allocationDate, $amount));
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
        if (! $this->math->isZero($previouslyAllocated)) {
            throw new InvalidArgumentException('Payment is already allocated to this invoice.');
        }

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
            'allocation_method' => $allocation->allocationMethod,
            'status' => AllocationStatus::Active->value,
            'metadata' => $allocation->metadata,
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

        $payment = $this->statuses->applyCalculatedStatus(
            $payment->refresh(),
            $calculation->totalAmount,
            $calculation->allocatedAmount,
            $calculation->refundedAmount,
            reason: 'Payment allocation recalculated.',
        );
        $this->unappliedBalances->sync($payment);

        return $payment->refresh();
    }

    private function availableAmount(Payment $payment): string
    {
        return $this->math->normalize((string) $payment->unapplied_amount);
    }

    /**
     * @return list<PaymentAllocationData>
     */
    private function fifoAllocations(Payment $payment, string $allocationDate, ?string $amount = null): array
    {
        if ($payment->party_type === null || $payment->party_id === null) {
            throw new InvalidArgumentException('FIFO allocation requires a payment party.');
        }

        $remaining = $amount === null
            ? $this->availableAmount($payment)
            : $this->math->normalize($amount);

        $this->validator->assertPositive($remaining, 'FIFO allocation amount');

        $invoiceQuery = Invoice::query()
            ->join('invoice_balances', 'invoice_balances.invoice_id', '=', 'invoices.id')
            ->where('invoices.tenant_id', $payment->tenant_id)
            ->where('invoices.party_type', $payment->party_type)
            ->where('invoices.party_id', $payment->party_id)
            ->where('invoice_balances.remaining_amount', '>', '0')
            ->whereNotIn('invoices.status', ['draft', 'cancelled', 'void'])
            ->orderBy('invoices.invoice_date')
            ->orderBy('invoices.id');

        $payment->organization_unit_id === null
            ? $invoiceQuery->whereNull('invoices.organization_unit_id')
            : $invoiceQuery->where('invoices.organization_unit_id', $payment->organization_unit_id);

        $invoiceIds = $invoiceQuery
            ->pluck('invoices.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $allocations = [];
        foreach ($invoiceIds as $invoiceId) {
            if ($this->math->isZero($remaining)) {
                break;
            }

            $balance = InvoiceBalance::query()->where('invoice_id', $invoiceId)->firstOrFail();
            $invoiceRemaining = (string) $balance->remaining_amount;
            $allocatedAmount = $this->math->compare($remaining, $invoiceRemaining) > 0
                ? $invoiceRemaining
                : $remaining;

            $allocations[] = new PaymentAllocationData(
                invoiceId: $invoiceId,
                allocatedAmount: $allocatedAmount,
                allocationDate: $allocationDate,
                allocationMethod: 'fifo',
            );
            $remaining = $this->math->sub($remaining, $allocatedAmount);
        }

        if ($allocations === []) {
            throw new InvalidArgumentException('No payable invoices were found for FIFO allocation.');
        }

        return $allocations;
    }
}
