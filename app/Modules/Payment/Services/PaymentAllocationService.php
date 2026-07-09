<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Invoice\DTOs\BalanceResultData;
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
        private readonly PaymentAllocationStateService $allocationStates,
        private readonly PaymentBalanceSynchronizer $balances,
        private readonly InvoiceBalanceProviderInterface $invoiceBalances,
        private readonly InvoiceSettlementServiceInterface $invoiceSettlements,
    ) {}

    public function createPending(Payment $payment, array $allocations): Payment
    {
        return DB::transaction(function () use ($payment, $allocations): Payment {
            $locked = Payment::query()
                ->lockForUpdate()
                ->with(['lines', 'allocations'])
                ->findOrFail($payment->getKey());
            $pendingTotal = '0.000000';

            foreach ($allocations as $allocation) {
                if (! $allocation instanceof PaymentAllocationData) {
                    throw new InvalidArgumentException('Payment allocations must be PaymentAllocationData instances.');
                }

                $pendingTotal = $this->math->add($pendingTotal, $allocation->allocatedAmount);
                if ($this->math->compare($pendingTotal, (string) $locked->total_amount) > 0) {
                    throw new InvalidArgumentException('Payment allocation total cannot exceed payment total.');
                }

                $this->createPendingOne($locked, $allocation);
            }

            $this->bumpVersion($locked);
            $this->balances->sync($locked->refresh(), 'Pending payment allocations recorded.');

            return $locked->refresh()->load(['lines', 'allocations', 'unappliedBalance']);
        });
    }

    public function allocate(
        Payment $payment,
        array $allocations,
        int $expectedVersion,
        ?int $actorId = null,
    ): Payment {
        return DB::transaction(function () use ($payment, $allocations, $expectedVersion, $actorId): Payment {
            $locked = Payment::query()
                ->lockForUpdate()
                ->with(['lines', 'allocations'])
                ->findOrFail($payment->getKey());
            $this->assertVersion($locked, $expectedVersion);
            $this->allocationStates->assertAllocatable($locked);

            foreach ($allocations as $allocation) {
                if (! $allocation instanceof PaymentAllocationData) {
                    throw new InvalidArgumentException('Payment allocations must be PaymentAllocationData instances.');
                }

                $this->allocateOne($locked, $allocation, $actorId);
                $locked = $this->balances->sync(
                    $locked->refresh(),
                    'Payment allocation recalculated.',
                    $actorId,
                );
            }

            $this->bumpVersion($locked);

            return $locked->refresh()->load([
                'lines',
                'allocations',
                'unappliedBalance',
                'lifecycleEvents',
            ]);
        });
    }

    public function realizePending(Payment $payment, ?int $actorId = null): Payment
    {
        return DB::transaction(function () use ($payment, $actorId): Payment {
            $locked = Payment::query()
                ->lockForUpdate()
                ->with(['allocations'])
                ->findOrFail($payment->getKey());

            $pendingAllocations = $locked->allocations()
                ->where('status', AllocationStatus::Pending->value)
                ->orderBy('invoice_id')
                ->orderBy('id')
                ->get();

            foreach ($pendingAllocations as $allocation) {
                $data = new PaymentAllocationData(
                    invoiceId: (int) $allocation->invoice_id,
                    allocatedAmount: (string) $allocation->allocated_amount,
                    allocationDate: $allocation->allocation_date->toDateString(),
                    allocationMethod: (string) $allocation->allocation_method,
                    metadata: is_array($allocation->metadata) ? $allocation->metadata : null,
                );
                $invoiceBalance = $this->payableInvoiceBalance($locked, (int) $allocation->invoice_id);
                $this->validator->validateInvoiceAllocation($locked, $invoiceBalance, $data);

                if ($this->math->compare((string) $allocation->allocated_amount, $invoiceBalance->remainingAmount) > 0) {
                    throw new InvalidArgumentException('Payment allocation cannot exceed invoice remaining balance.');
                }

                $settlement = $this->invoiceSettlements->applyPaymentAllocation(
                    (int) $allocation->invoice_id,
                    (string) $allocation->allocated_amount,
                    false,
                );
                $allocation->forceFill([
                    'invoice_balance_before' => $invoiceBalance->remainingAmount,
                    'invoice_balance_after' => $settlement->balanceAfter,
                    'status' => AllocationStatus::Active->value,
                    'realized_at' => now(),
                    'realized_by' => $actorId,
                    'row_version' => (int) $allocation->row_version + 1,
                ])->save();
                $locked = $this->balances->sync(
                    $locked->refresh(),
                    'Pending payment allocation realized.',
                    $actorId,
                );
            }

            if ($pendingAllocations->isNotEmpty()) {
                $this->bumpVersion($locked);
            }

            return $locked->refresh()->load([
                'lines',
                'allocations',
                'unappliedBalance',
                'lifecycleEvents',
            ]);
        });
    }

    public function allocateByMethod(
        Payment $payment,
        string $method,
        string $allocationDate,
        int $expectedVersion,
        array $allocations = [],
        ?string $amount = null,
        ?int $actorId = null,
    ): Payment {
        $method = strtolower(trim($method));
        if (in_array($method, ['manual', 'specific_invoice'], true)) {
            return $this->allocate(
                $payment,
                array_map(
                    static fn (PaymentAllocationData $allocation): PaymentAllocationData => new PaymentAllocationData(
                        invoiceId: $allocation->invoiceId,
                        allocatedAmount: $allocation->allocatedAmount,
                        allocationDate: $allocation->allocationDate,
                        allocationMethod: $method,
                        metadata: $allocation->metadata,
                    ),
                    $allocations,
                ),
                $expectedVersion,
                $actorId,
            );
        }
        if ($method !== 'fifo') {
            throw new InvalidArgumentException('Unsupported payment allocation method.');
        }

        return $this->allocate(
            $payment,
            $this->fifoAllocations($payment, $allocationDate, $amount),
            $expectedVersion,
            $actorId,
        );
    }

    public function syncPaymentAmounts(Payment $payment, ?int $actorId = null): Payment
    {
        return $this->balances->sync($payment, 'Payment allocation recalculated.', $actorId);
    }

    private function createPendingOne(Payment $payment, PaymentAllocationData $allocation): PaymentAllocation
    {
        $invoiceBalance = $this->payableInvoiceBalance($payment, $allocation->invoiceId);
        $this->validator->validateInvoiceAllocation($payment, $invoiceBalance, $allocation);
        if ($this->math->compare($allocation->allocatedAmount, $invoiceBalance->remainingAmount) > 0) {
            throw new InvalidArgumentException('Payment allocation cannot exceed invoice remaining balance.');
        }
        $this->assertNoExistingPaymentInvoiceAllocation($payment, $allocation->invoiceId);
        $snapshot = $this->invoiceSnapshot($allocation->invoiceId);

        return PaymentAllocation::query()->create([
            'tenant_id' => $payment->tenant_id,
            'organization_unit_id' => $payment->organization_unit_id,
            'payment_id' => $payment->getKey(),
            'invoice_id' => $allocation->invoiceId,
            ...$snapshot,
            'invoice_total' => $invoiceBalance->totalAmount,
            'invoice_balance_before' => $invoiceBalance->remainingAmount,
            'previously_allocated_amount' => '0.000000',
            'allocated_amount' => $this->math->normalize($allocation->allocatedAmount),
            'invoice_balance_after' => $invoiceBalance->remainingAmount,
            'allocation_date' => $allocation->allocationDate,
            'allocation_method' => $allocation->allocationMethod,
            'status' => AllocationStatus::Pending->value,
            'metadata' => array_merge($allocation->metadata ?? [], [
                'projected_invoice_balance_after' => $this->math->sub(
                    $invoiceBalance->remainingAmount,
                    $allocation->allocatedAmount,
                ),
            ]),
        ]);
    }

    private function allocateOne(
        Payment $payment,
        PaymentAllocationData $allocation,
        ?int $actorId,
    ): PaymentAllocation {
        $invoiceBalance = $this->payableInvoiceBalance($payment, $allocation->invoiceId);
        $this->validator->validateInvoiceAllocation($payment, $invoiceBalance, $allocation);
        if ($this->math->compare($allocation->allocatedAmount, $this->availableAmount($payment)) > 0) {
            throw new InvalidArgumentException('Payment allocation cannot exceed available payment amount.');
        }
        if ($this->math->compare($allocation->allocatedAmount, $invoiceBalance->remainingAmount) > 0) {
            throw new InvalidArgumentException('Payment allocation cannot exceed invoice remaining balance.');
        }
        $this->assertNoExistingPaymentInvoiceAllocation($payment, $allocation->invoiceId);
        $snapshot = $this->invoiceSnapshot($allocation->invoiceId);
        $settlement = $this->invoiceSettlements->applyPaymentAllocation(
            $allocation->invoiceId,
            $allocation->allocatedAmount,
            false,
        );

        return PaymentAllocation::query()->create([
            'tenant_id' => $payment->tenant_id,
            'organization_unit_id' => $payment->organization_unit_id,
            'payment_id' => $payment->getKey(),
            'invoice_id' => $allocation->invoiceId,
            ...$snapshot,
            'invoice_total' => $invoiceBalance->totalAmount,
            'invoice_balance_before' => $invoiceBalance->remainingAmount,
            'previously_allocated_amount' => '0.000000',
            'allocated_amount' => $this->math->normalize($allocation->allocatedAmount),
            'invoice_balance_after' => $settlement->balanceAfter,
            'allocation_date' => $allocation->allocationDate,
            'allocation_method' => $allocation->allocationMethod,
            'status' => AllocationStatus::Active->value,
            'realized_at' => now(),
            'realized_by' => $actorId,
            'metadata' => $allocation->metadata,
        ]);
    }

    private function invoiceSnapshot(int $invoiceId): array
    {
        $reference = $this->invoiceBalances->getInvoiceReferences([$invoiceId])[$invoiceId] ?? null;
        if (! is_array($reference)) {
            throw new InvalidArgumentException('Invoice reference snapshot could not be resolved.');
        }

        $invoiceNumber = trim((string) ($reference['invoice_number'] ?? ''));
        if ($invoiceNumber === '') {
            throw new InvalidArgumentException('Invoice number is required for payment allocation history.');
        }

        return [
            'invoice_number_snapshot' => $invoiceNumber,
            'invoice_date_snapshot' => $reference['invoice_date'] ?? null,
            'invoice_currency_code_snapshot' => $reference['currency_code'] ?? null,
        ];
    }

    private function payableInvoiceBalance(Payment $payment, int $invoiceId): BalanceResultData
    {
        if ($payment->party_type === null || $payment->party_id === null) {
            throw new InvalidArgumentException('Payment invoice allocation requires a payment party.');
        }

        return $this->invoiceBalances->validatePayableState(
            invoiceId: $invoiceId,
            tenantId: (int) $payment->tenant_id,
            organizationUnitId: $payment->organization_unit_id,
            partyType: (string) $payment->party_type,
            partyId: (int) $payment->party_id,
            currencyId: $payment->currency_id === null ? null : (int) $payment->currency_id,
        );
    }

    private function availableAmount(Payment $payment): string
    {
        return $this->math->normalize((string) $payment->unapplied_amount);
    }

    private function assertNoExistingPaymentInvoiceAllocation(Payment $payment, int $invoiceId): void
    {
        if (PaymentAllocation::query()
            ->where('payment_id', $payment->getKey())
            ->where('invoice_id', $invoiceId)
            ->whereIn('status', [AllocationStatus::Pending->value, AllocationStatus::Active->value])
            ->exists()) {
            throw new InvalidArgumentException('Payment is already allocated to this invoice.');
        }
    }

    private function fifoAllocations(Payment $payment, string $allocationDate, ?string $amount = null): array
    {
        if ($payment->party_type === null || $payment->party_id === null) {
            throw new InvalidArgumentException('FIFO allocation requires a payment party.');
        }

        $remaining = $amount === null
            ? $this->availableAmount($payment)
            : $this->math->normalize($amount);
        $this->validator->assertPositive($remaining, 'FIFO allocation amount');
        $allocations = [];

        foreach ($this->invoiceBalances->getPayableBalancesForParty(
            tenantId: (int) $payment->tenant_id,
            organizationUnitId: $payment->organization_unit_id,
            partyType: $payment->party_type,
            partyId: (int) $payment->party_id,
        ) as $invoiceBalance) {
            if ($this->math->isZero($remaining)) {
                break;
            }

            $allocatedAmount = $this->math->compare($remaining, $invoiceBalance->remainingAmount) > 0
                ? $invoiceBalance->remainingAmount
                : $remaining;
            $allocations[] = new PaymentAllocationData(
                invoiceId: $invoiceBalance->sourceId,
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

    private function assertVersion(Payment $payment, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $payment->row_version !== $expectedVersion) {
            throw new InvalidArgumentException('Payment was changed by another request. Reload it before allocating.');
        }
    }

    private function bumpVersion(Payment $payment): void
    {
        $payment->forceFill([
            'row_version' => (int) $payment->row_version + 1,
        ])->save();
    }
}
