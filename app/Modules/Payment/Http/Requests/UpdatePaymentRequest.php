<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\DTOs\UpdatePaymentData;
use Modules\Payment\Http\Requests\Concerns\BuildsPaymentAllocations;
use Modules\Payment\Http\Requests\Concerns\BuildsPaymentLines;

final class UpdatePaymentRequest extends TenantScopedRequest
{
    use BuildsPaymentAllocations;
    use BuildsPaymentLines;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'payment_date' => ['required', 'date'],
            'party_type' => ['nullable', 'string', 'max:150'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['required_with:currency_id', 'nullable', 'decimal:0,6', 'gt:0'],
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

    public function toData(): UpdatePaymentData
    {
        return new UpdatePaymentData(
            expectedVersion: (int) $this->input('expected_version'),
            paymentDate: (string) $this->input('payment_date'),
            partyType: $this->stringOrNull('party_type'),
            partyId: $this->intOrNull('party_id'),
            currencyId: $this->intOrNull('currency_id'),
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            referenceNumber: $this->stringOrNull('reference_number'),
            chequeNumber: $this->stringOrNull('cheque_number'),
            chequeDate: $this->stringOrNull('cheque_date'),
            payeeName: $this->stringOrNull('payee_name'),
            notes: $this->stringOrNull('notes'),
            updatedBy: $this->currentUserId(),
            lines: $this->paymentLineData(),
            allocations: $this->paymentAllocationData(),
        );
    }

    public function expectedVersion(): int
    {
        return (int) $this->input('expected_version');
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $merges = [];
        foreach ([
            'party_type', 'party_id', 'currency_id', 'exchange_rate',
            'reference_number', 'cheque_number', 'cheque_date', 'payee_name', 'notes',
        ] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim($this->input($field)) === '') {
                $merges[$field] = null;
            }
        }

        if ($merges !== []) {
            $this->merge($merges);
        }
    }
}
