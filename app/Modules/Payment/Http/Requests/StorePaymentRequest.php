<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\Constants\PaymentIdempotency;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Http\Requests\Concerns\BuildsPaymentAllocations;

final class StorePaymentRequest extends TenantScopedRequest
{
    use BuildsPaymentAllocations;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            PaymentIdempotency::REQUEST_ATTRIBUTE => [
                'required',
                'string',
                'max:'.PaymentIdempotency::MAX_KEY_LENGTH,
            ],
            'payment_type' => ['required', Rule::enum(PaymentType::class)],
            'direction' => ['required', Rule::enum(PaymentDirection::class)],
            'payment_date' => ['required', 'date'],
            'party_type' => ['nullable', 'string', 'max:150'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'cheque_number' => ['nullable', 'string', 'max:100'],
            'cheque_date' => ['nullable', 'date'],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            ...$this->paymentLineRules(),
            ...$this->paymentAllocationRules(['nullable', 'array']),
            'payment_number' => ['prohibited'],
            'status' => ['prohibited'],
            'document_status' => ['prohibited'],
            'allocation_status' => ['prohibited'],
            'posting_status' => ['prohibited'],
            'instrument_status' => ['prohibited'],
            'source_type' => ['prohibited'],
            'source_id' => ['prohibited'],
            'amount_in_words' => ['prohibited'],
            'metadata' => ['prohibited'],
        ];
    }

    public function toData(): CreatePaymentData
    {
        return new CreatePaymentData(
            tenantId: $this->tenantId(),
            paymentType: PaymentType::from((string) $this->input('payment_type')),
            direction: PaymentDirection::from((string) $this->input('direction')),
            paymentDate: (string) $this->input('payment_date'),
            organizationUnitId: $this->organizationUnitId(),
            partyType: $this->stringOrNull('party_type'),
            partyId: $this->intOrNull('party_id'),
            currencyId: $this->intOrNull('currency_id'),
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            referenceNumber: $this->stringOrNull('reference_number'),
            chequeNumber: $this->stringOrNull('cheque_number'),
            chequeDate: $this->stringOrNull('cheque_date'),
            payeeName: $this->stringOrNull('payee_name'),
            notes: $this->stringOrNull('notes'),
            createdBy: $this->currentUserId(),
            lines: $this->paymentLineData(),
            allocations: $this->paymentAllocationData(),
            idempotencyKey: (string) $this->input(PaymentIdempotency::REQUEST_ATTRIBUTE),
        );
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $header = $this->header(PaymentIdempotency::REQUEST_HEADER);
        $this->merge([
            PaymentIdempotency::REQUEST_ATTRIBUTE => is_string($header) ? trim($header) : null,
        ]);
    }

    private function paymentLineRules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.payment_method_id' => ['required', 'integer', 'min:1'],
            'lines.*.reference_number' => ['nullable', 'string', 'max:150'],
            'lines.*.amount' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.instrument_direction' => ['nullable', Rule::in(['received', 'issued'])],
            'lines.*.external_bank_name' => ['nullable', 'string', 'max:150'],
            'lines.*.external_bank_branch' => ['nullable', 'string', 'max:150'],
            'lines.*.instrument_number' => ['nullable', 'string', 'max:150'],
            'lines.*.instrument_date' => ['nullable', 'date'],
            'lines.*.notes' => ['nullable', 'string'],
            'lines.*.cleared_amount' => ['prohibited'],
            'lines.*.status' => ['prohibited'],
            'lines.*.deposit_date' => ['prohibited'],
            'lines.*.realized_date' => ['prohibited'],
            'lines.*.clearing_date' => ['prohibited'],
            'lines.*.bounced_date' => ['prohibited'],
            'lines.*.returned_date' => ['prohibited'],
            'lines.*.cancellation_reason' => ['prohibited'],
            'lines.*.metadata' => ['prohibited'],
        ];
    }

    private function paymentLineData(): array
    {
        return array_map(static fn (array $row): PaymentLineData => new PaymentLineData(
            amount: (string) $row['amount'],
            paymentMethodId: isset($row['payment_method_id']) ? (int) $row['payment_method_id'] : null,
            referenceNumber: $row['reference_number'] ?? null,
            notes: $row['notes'] ?? null,
            instrumentDirection: $row['instrument_direction'] ?? null,
            externalBankName: $row['external_bank_name'] ?? null,
            externalBankBranch: $row['external_bank_branch'] ?? null,
            instrumentNumber: $row['instrument_number'] ?? null,
            instrumentDate: $row['instrument_date'] ?? null,
        ), $this->input('lines'));
    }

    private function intOrNull(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
