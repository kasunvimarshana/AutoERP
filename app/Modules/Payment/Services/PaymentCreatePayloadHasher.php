<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use JsonException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;

final class PaymentCreatePayloadHasher
{
    public function __construct(private readonly DecimalMath $math) {}

    /** @throws JsonException */
    public function hash(CreatePaymentData $data): string
    {
        $payload = [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'payment_type' => $data->paymentType->value,
            'direction' => $data->direction->value,
            'payment_date' => $data->paymentDate,
            'party_type' => $data->partyType,
            'party_id' => $data->partyId,
            'source_type' => $data->sourceType,
            'source_id' => $data->sourceId,
            'original_payment_id' => $data->originalPaymentId,
            'currency_id' => $data->currencyId,
            'exchange_rate' => $this->math->normalize($data->exchangeRate),
            'reference_number' => $data->referenceNumber,
            'notes' => $data->notes,
            'created_by' => $data->createdBy,
            'cheque_number' => $data->chequeNumber,
            'cheque_date' => $data->chequeDate,
            'payee_name' => $data->payeeName,
            'metadata' => $data->metadata,
            'lines' => array_map(fn (PaymentLineData $line): array => $this->line($line), $data->lines),
            'allocations' => array_map(
                fn (PaymentAllocationData $allocation): array => $this->allocation($allocation),
                $data->allocations,
            ),
        ];

        return hash('sha256', json_encode(
            $this->canonicalize($payload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function line(PaymentLineData $line): array
    {
        return [
            'payment_method_id' => $line->paymentMethodId,
            'reference_number' => $line->referenceNumber,
            'amount' => $this->math->normalize($line->amount),
            'cleared_amount' => $this->math->normalize($line->clearedAmount),
            'status' => $line->status,
            'notes' => $line->notes,
            'metadata' => $line->metadata,
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
        ];
    }

    private function allocation(PaymentAllocationData $allocation): array
    {
        return [
            'invoice_id' => $allocation->invoiceId,
            'allocated_amount' => $this->math->normalize($allocation->allocatedAmount),
            'allocation_date' => $allocation->allocationDate,
            'allow_overpayment' => $allocation->allowOverpayment,
            'allocation_method' => $allocation->allocationMethod,
            'metadata' => $allocation->metadata,
        ];
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
