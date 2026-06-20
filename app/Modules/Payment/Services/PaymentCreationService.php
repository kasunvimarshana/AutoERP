<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Validators\PaymentValidationService;

final class PaymentCreationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentValidationService $validator,
        private readonly PaymentNumberService $numbers,
        private readonly PaymentCalculationService $calculations,
        private readonly PaymentUnappliedBalanceService $unappliedBalances,
        private readonly PaymentAllocationService $allocations,
        private readonly PaymentStatusService $statuses,
    ) {}

    public function create(CreatePaymentData $data): Payment
    {
        $this->validator->validateForCreation($data);

        return DB::transaction(function () use ($data): Payment {
            $calculation = $this->calculations->calculateForCreation($data);

            $payment = Payment::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'payment_number' => $this->numbers->resolve($data),
                'payment_type' => $data->paymentType->value,
                'direction' => $data->direction->value,
                'party_type' => $data->partyType,
                'party_id' => $data->partyId,
                'source_type' => $data->sourceType,
                'source_id' => $data->sourceId,
                'allocation_status' => $data->allocationStatus,
                'payment_date' => $data->paymentDate,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'reference_number' => $data->referenceNumber,
                'cheque_number' => $data->chequeNumber,
                'cheque_date' => $data->chequeDate,
                'bank_account_id' => $data->bankAccountId,
                'payee_name' => $data->payeeName,
                'amount_in_words' => $data->amountInWords,
                'status' => $data->status->value,
                'document_status' => $this->documentStatusFor($data->status)->value,
                'posting_status' => $this->postingStatusFor($data->status)->value,
                'instrument_status' => $this->initialInstrumentStatus($data)->value,
                'total_amount' => $calculation->totalAmount,
                'allocated_amount' => $calculation->allocatedAmount,
                'unapplied_amount' => $calculation->unappliedAmount,
                'refunded_amount' => $calculation->refundedAmount,
                'notes' => $data->notes,
                'metadata' => $data->metadata,
                'created_by' => $data->createdBy,
            ]);
            $this->statuses->recordInitial($payment, $data->createdBy);

            foreach ($data->lines as $index => $line) {
                PaymentLine::query()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'payment_id' => $payment->getKey(),
                    'payment_method_id' => $line->paymentMethodId,
                    'reference_number' => $line->referenceNumber,
                    'amount' => $calculation->lineAmounts[$index],
                    'cleared_amount' => $this->math->normalize($line->clearedAmount),
                    'status' => $line->status,
                    'internal_bank_account_id' => $line->internalBankAccountId,
                    'instrument_direction' => $line->instrumentDirection,
                    'external_bank_name' => $line->externalBankName,
                    'external_bank_branch' => $line->externalBankBranch,
                    'instrument_number' => $line->instrumentNumber,
                    'instrument_date' => $line->instrumentDate,
                    'deposit_date' => $line->depositDate,
                    'realized_date' => $line->realizedDate,
                    'clearing_date' => $line->clearingDate,
                    'bounced_date' => $line->bouncedDate,
                    'returned_date' => $line->returnedDate,
                    'cancellation_reason' => $line->cancellationReason,
                    'notes' => $line->notes,
                    'metadata' => $line->metadata,
                ]);
            }

            if ($data->allocations !== []) {
                $payment = $this->allocations->createPending($payment->refresh(), $data->allocations);
                if ($data->status === PaymentStatus::Posted) {
                    $payment = $this->allocations->realizePending($payment->refresh(), $data->createdBy);
                }
            } else {
                $this->unappliedBalances->sync($payment->refresh());
            }

            return $payment->load(['lines', 'allocations', 'unappliedBalance', 'refunds', 'reversals']);
        });
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

    private function initialInstrumentStatus(CreatePaymentData $data): PaymentInstrumentStatus
    {
        foreach ($data->lines as $line) {
            if (! in_array(strtolower($line->status), ['pending', 'draft'], true)) {
                return PaymentInstrumentStatus::tryFrom(strtolower($line->status)) ?? PaymentInstrumentStatus::Pending;
            }
        }

        return PaymentInstrumentStatus::Pending;
    }
}
