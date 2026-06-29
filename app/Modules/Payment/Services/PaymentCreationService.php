<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentLifecycleDimension;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Models\PaymentMethod;
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
        private readonly PaymentLifecycleEventRecorder $events,
        private readonly PaymentReferenceSnapshotService $snapshots,
    ) {}

    public function create(CreatePaymentData $data): Payment
    {
        $this->validator->validateForCreation($data);

        return DB::transaction(function () use ($data): Payment {
            $calculation = $this->calculations->calculateForCreation($data);
            $instrumentStatus = $this->initialInstrumentStatus($data);
            $payment = Payment::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'payment_number' => $this->numbers->resolve($data),
                'payment_type' => $data->paymentType->value,
                'direction' => $data->direction->value,
                'party_type' => $data->partyType,
                'party_id' => $data->partyId,
                ...$this->snapshots->header($data),
                'source_type' => $data->sourceType,
                'source_id' => $data->sourceId,
                'document_status' => PaymentDocumentStatus::Draft->value,
                'allocation_status' => PaymentAllocationState::Unallocated->value,
                'posting_status' => PaymentPostingStatus::NotPosted->value,
                'instrument_status' => $instrumentStatus->value,
                'payment_date' => $data->paymentDate,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'reference_number' => $data->referenceNumber,
                'cheque_number' => $data->chequeNumber,
                'cheque_date' => $data->chequeDate,
                'payee_name' => $data->payeeName,
                'amount_in_words' => $data->amountInWords,
                'total_amount' => $calculation->totalAmount,
                'allocated_amount' => $calculation->allocatedAmount,
                'unapplied_amount' => $calculation->unappliedAmount,
                'refunded_amount' => $calculation->refundedAmount,
                'notes' => $data->notes,
                'metadata' => $data->metadata,
                'created_by' => $data->createdBy,
            ]);

            $this->recordInitialEvents($payment, $instrumentStatus, $data->createdBy);
            foreach ($data->lines as $index => $line) {
                $method = PaymentMethod::query()->find($line->paymentMethodId);
                if (! $method instanceof PaymentMethod) {
                    throw new InvalidArgumentException('Payment method was not found while creating payment lines.');
                }
                PaymentLine::query()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'payment_id' => $payment->getKey(),
                    'line_number' => $index + 1,
                    'payment_method_id' => $method->getKey(),
                    'payment_method_code_snapshot' => (string) $method->code,
                    'payment_method_name_snapshot' => (string) $method->name,
                    'payment_method_type_snapshot' => $method->method_type instanceof \BackedEnum ? $method->method_type->value : (string) $method->method_type,
                    'requires_reference_snapshot' => (bool) $method->requires_reference,
                    'requires_instrument_details_snapshot' => (bool) $method->requires_instrument_details,
                    'reference_number' => $line->referenceNumber,
                    'amount' => $calculation->lineAmounts[$index],
                    'cleared_amount' => $this->math->normalize($line->clearedAmount),
                    'status' => $line->status,
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
            } else {
                $this->unappliedBalances->sync($payment->refresh());
            }

            return $payment->refresh()->load(['lines', 'allocations', 'unappliedBalance', 'refunds', 'reversals', 'lifecycleEvents']);
        });
    }

    private function recordInitialEvents(Payment $payment, PaymentInstrumentStatus $instrumentStatus, ?int $actorId): void
    {
        $this->events->record($payment, PaymentLifecycleDimension::Document, null, PaymentDocumentStatus::Draft, $actorId, 'Payment created.');
        $this->events->record($payment, PaymentLifecycleDimension::Posting, null, PaymentPostingStatus::NotPosted, $actorId);
        $this->events->record($payment, PaymentLifecycleDimension::Allocation, null, PaymentAllocationState::Unallocated, $actorId);
        $this->events->record($payment, PaymentLifecycleDimension::Instrument, null, $instrumentStatus, $actorId);
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
