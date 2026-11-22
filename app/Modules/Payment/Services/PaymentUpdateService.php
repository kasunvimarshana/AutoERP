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
use Modules\Core\Services\DecimalMath;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\DTOs\UpdatePaymentData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentInstrumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Validators\PaymentValidationService;

final class PaymentUpdateService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentValidationService $validator,
        private readonly PaymentCalculationService $calculations,
        private readonly PaymentUnappliedBalanceService $unappliedBalances,
        private readonly PaymentAllocationService $allocations,
        private readonly PaymentReferenceSnapshotService $snapshots,
        private readonly PaymentBalanceSynchronizer $balances,
    ) {}

    public function update(Payment $payment, UpdatePaymentData $data): Payment
    {
        return DB::transaction(function () use ($payment, $data): Payment {
            $locked = Payment::query()
                ->lockForUpdate()
                ->with(['lines', 'allocations', 'unappliedBalance'])
                ->findOrFail($payment->getKey());

            if ($data->expectedVersion < 1 || (int) $locked->row_version !== $data->expectedVersion) {
                throw new InvalidArgumentException('Payment was changed by another request. Reload it before updating.');
            }

            $docStatus = $locked->document_status instanceof PaymentDocumentStatus
                ? $locked->document_status
                : PaymentDocumentStatus::from((string) $locked->document_status);

            if ($docStatus !== PaymentDocumentStatus::Draft) {
                throw new InvalidArgumentException('Only draft payments can be modified.');
            }

            $postStatus = $locked->posting_status instanceof PaymentPostingStatus
                ? $locked->posting_status
                : PaymentPostingStatus::from((string) $locked->posting_status);

            if ($postStatus !== PaymentPostingStatus::NotPosted) {
                throw new InvalidArgumentException('Only unposted payments can be modified.');
            }

            $paymentType = $locked->payment_type instanceof PaymentType
                ? $locked->payment_type
                : PaymentType::from((string) $locked->payment_type);
            $direction = $locked->direction instanceof PaymentDirection
                ? $locked->direction
                : PaymentDirection::from((string) $locked->direction);

            $snapshotData = new CreatePaymentData(
                tenantId: (int) $locked->tenant_id,
                paymentType: $paymentType,
                direction: $direction,
                paymentDate: $data->paymentDate,
                organizationUnitId: $locked->organization_unit_id,
                partyType: $data->partyType,
                partyId: $data->partyId,
                sourceType: $locked->source_type,
                sourceId: $locked->source_id ? (int) $locked->source_id : null,
                currencyId: $data->currencyId,
                exchangeRate: $data->exchangeRate,
                createdBy: $data->updatedBy,
                lines: $data->lines,
                allocations: $data->allocations,
                metadata: is_array($locked->metadata) ? $locked->metadata : null,
                originalPaymentId: $locked->original_payment_id ? (int) $locked->original_payment_id : null,
            );

            $this->validator->validateForCreation($snapshotData);
            $snapshots = $this->snapshots->header($snapshotData);
            $calculation = $this->calculations->calculateForCreation($snapshotData);
            $instrumentStatus = $this->resolveInstrumentStatus($data->lines);

            // Replace lines
            $locked->lines()->delete();
            foreach ($data->lines as $index => $line) {
                $method = PaymentMethod::query()->find($line->paymentMethodId);
                if (! $method instanceof PaymentMethod) {
                    throw new InvalidArgumentException('Payment method was not found while updating payment lines.');
                }

                $paymentLine = new PaymentLine();
                $paymentLine->forceFill([
                    'tenant_id' => $locked->tenant_id,
                    'organization_unit_id' => $locked->organization_unit_id,
                    'payment_id' => $locked->getKey(),
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
                    'metadata' => $this->lineMetadata($line),
                ]);
                $paymentLine->save();
            }

            // Remove existing pending allocations
            $locked->allocations()->where('status', AllocationStatus::Pending->value)->delete();

            // Update payment record attributes
            $locked->forceFill([
                'party_type' => $data->partyType,
                'party_id' => $data->partyId,
                ...$snapshots,
                'payment_date' => $data->paymentDate,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'reference_number' => $data->referenceNumber,
                'cheque_number' => $data->chequeNumber,
                'cheque_date' => $data->chequeDate,
                'payee_name' => $data->payeeName,
                'total_amount' => $calculation->totalAmount,
                'allocated_amount' => $calculation->allocatedAmount,
                'unapplied_amount' => $calculation->unappliedAmount,
                'refunded_amount' => $calculation->refundedAmount,
                'instrument_status' => $instrumentStatus->value,
                'notes' => $data->notes,
                'row_version' => (int) $locked->row_version + 1,
            ]);
            $locked->save();

            // Replace pending allocations if provided
            if ($data->allocations !== []) {
                $locked = $this->allocations->createPending($locked->refresh(), $data->allocations);
            } else {
                $locked = $this->balances->sync($locked->refresh(), 'Draft payment updated without allocations.', $data->updatedBy);
            }

            return $locked->refresh()->load([
                'lines',
                'allocations',
                'unappliedBalance',
                'lifecycleEvents',
                'currency',
            ]);
        });
    }

    private function lineMetadata(PaymentLineData $line): ?array
    {
        $metadata = $line->metadata ?? [];
        if (isset($metadata['cash_received_amount'])) {
            $metadata['cash_received_amount'] = $this->math->normalize($metadata['cash_received_amount']);
            $metadata['cash_change_amount'] = $this->math->sub($metadata['cash_received_amount'], $line->amount);
        }

        return $metadata === [] ? null : $metadata;
    }

    private function resolveInstrumentStatus(array $lines): PaymentInstrumentStatus
    {
        foreach ($lines as $line) {
            if ($line instanceof PaymentLineData && ! in_array(strtolower($line->status), ['pending', 'draft'], true)) {
                return PaymentInstrumentStatus::tryFrom(strtolower($line->status)) ?? PaymentInstrumentStatus::Pending;
            }
        }

        return PaymentInstrumentStatus::Pending;
    }
}
