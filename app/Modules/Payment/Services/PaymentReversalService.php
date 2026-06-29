<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePaymentReversalInterface;
use Modules\Invoice\Contracts\InvoiceSettlementServiceInterface;
use Modules\Payment\DTOs\PaymentReversalData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentLifecycleDimension;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\UnappliedBalanceStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentReversal;

final class PaymentReversalService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceSettlementServiceInterface $invoiceSettlements,
        private readonly PaymentReversalNumberService $numbers,
        private readonly FinancePaymentReversalInterface $financeReversals,
        private readonly PaymentLifecycleEventRecorder $events,
    ) {}

    public function reverse(PaymentReversalData $data): PaymentReversal
    {
        return DB::transaction(function () use ($data): PaymentReversal {
            $payment = Payment::query()
                ->with(['allocations', 'unappliedBalance'])
                ->lockForUpdate()
                ->findOrFail($data->paymentId);
            $this->assertVersion($payment, $data->expectedVersion);

            $documentBefore = $this->documentStatus($payment);
            $postingBefore = $this->postingStatus($payment);
            $allocationBefore = $this->allocationStatus($payment);
            $instrumentBefore = $this->instrumentStatus($payment);
            if ($payment->reversals()->exists()) {
                throw new InvalidArgumentException('Payment reversal already exists for this payment.');
            }
            if ($documentBefore !== PaymentDocumentStatus::Approved || $postingBefore !== PaymentPostingStatus::Posted) {
                throw new InvalidArgumentException('Only approved and posted payments can be reversed.');
            }
            if (trim((string) $payment->finance_posting_reference) === '') {
                throw new InvalidArgumentException('Payment cannot be reversed without a Finance posting reference.');
            }

            $financeReversal = $this->financeReversals->reversePayment(
                (int) $payment->tenant_id,
                $payment->organization_unit_id,
                (int) $payment->getKey(),
                $data->reversalDate,
                $data->reversedBy,
                $data->reason,
            );

            foreach ($payment->allocations()->where('status', AllocationStatus::Active->value)->orderBy('invoice_id')->orderBy('id')->get() as $allocation) {
                $this->invoiceSettlements->reversePaymentAllocation(
                    (int) $allocation->invoice_id,
                    (string) $allocation->allocated_amount,
                );
                $allocation->forceFill(['status' => AllocationStatus::Reversed->value])->save();
            }
            foreach ($payment->allocations()->where('status', AllocationStatus::Pending->value)->get() as $allocation) {
                $allocation->forceFill(['status' => AllocationStatus::Void->value])->save();
            }

            $reversal = PaymentReversal::query()->create([
                'tenant_id' => $payment->tenant_id,
                'organization_unit_id' => $payment->organization_unit_id,
                'payment_id' => $payment->getKey(),
                'finance_reversal_reference' => $financeReversal->journalNumber,
                'reversal_number' => $this->numbers->resolve($data, $payment),
                'reversal_date' => $data->reversalDate,
                'reason' => $data->reason,
                'reversed_by' => $data->reversedBy,
                'original_amount' => $payment->total_amount,
                'reversed_amount' => $this->math->normalize((string) $payment->total_amount),
            ]);

            $payment->forceFill([
                'document_status' => PaymentDocumentStatus::Reversed->value,
                'allocation_status' => PaymentAllocationState::Unallocated->value,
                'posting_status' => PaymentPostingStatus::Reversed->value,
                'instrument_status' => PaymentInstrumentStatus::Reversed->value,
                'allocated_amount' => '0.000000',
                'unapplied_amount' => '0.000000',
                'reversed_by' => $data->reversedBy,
                'reversed_at' => now(),
                'reversal_reason' => $data->reason,
                'row_version' => (int) $payment->row_version + 1,
            ])->save();
            $payment = $payment->refresh();
            $this->events->record($payment, PaymentLifecycleDimension::Document, $documentBefore, PaymentDocumentStatus::Reversed, $data->reversedBy, $data->reason);
            $this->events->record($payment, PaymentLifecycleDimension::Posting, $postingBefore, PaymentPostingStatus::Reversed, $data->reversedBy, $data->reason, [
                'finance_reversal_reference' => $financeReversal->journalNumber,
            ]);
            $this->events->record($payment, PaymentLifecycleDimension::Allocation, $allocationBefore, PaymentAllocationState::Unallocated, $data->reversedBy, $data->reason);
            $this->events->record($payment, PaymentLifecycleDimension::Instrument, $instrumentBefore, PaymentInstrumentStatus::Reversed, $data->reversedBy, $data->reason);

            if ($payment->unappliedBalance !== null) {
                $payment->unappliedBalance->forceFill([
                    'allocation_status' => PaymentAllocationState::Unallocated->value,
                    'allocated_amount' => '0.000000',
                    'remaining_amount' => '0.000000',
                    'status' => UnappliedBalanceStatus::Cancelled->value,
                ])->save();
            }

            return $reversal->refresh();
        });
    }

    private function assertVersion(Payment $payment, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $payment->row_version !== $expectedVersion) {
            throw new InvalidArgumentException('Payment was changed by another request. Reload it before reversing.');
        }
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

    private function allocationStatus(Payment $payment): PaymentAllocationState
    {
        return $payment->allocation_status instanceof PaymentAllocationState
            ? $payment->allocation_status
            : PaymentAllocationState::from((string) $payment->allocation_status);
    }

    private function instrumentStatus(Payment $payment): PaymentInstrumentStatus
    {
        if ($payment->instrument_status instanceof PaymentInstrumentStatus) {
            return $payment->instrument_status;
        }

        return PaymentInstrumentStatus::tryFrom((string) $payment->instrument_status) ?? PaymentInstrumentStatus::Pending;
    }
}
