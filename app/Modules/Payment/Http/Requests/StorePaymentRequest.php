<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Support\PaymentIdempotencyKey;

final class StorePaymentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'payment_type' => ['required', Rule::enum(PaymentType::class)],
            'direction' => ['required', Rule::enum(PaymentDirection::class)],
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
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', 'integer', 'min:1'],
            'allocations.*.allocated_amount' => ['required', 'decimal:0,6', 'gt:0'],
            'allocations.*.allocation_date' => ['nullable', 'date'],
            'allocations.*.allocation_method' => ['nullable', 'string', 'max:50'],
            'payment_number' => ['prohibited'],
            'source_type' => ['prohibited'],
            'source_id' => ['prohibited'],
            'original_payment_id' => ['prohibited'],
            'metadata' => ['prohibited'],
        ];
    }

    public function toData(): CreatePaymentData
    {
        $validated = $this->validated();

        return new CreatePaymentData(
            tenantId: $this->tenantId(),
            paymentType: PaymentType::from((string) $validated['payment_type']),
            direction: PaymentDirection::from((string) $validated['direction']),
            paymentDate: (string) $validated['payment_date'],
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
            lines: array_map(
                fn (array $line): PaymentLineData => $this->lineData($line),
                (array) ($validated['lines'] ?? []),
            ),
            allocations: array_map(
                static fn (array $row): PaymentAllocationData => new PaymentAllocationData(
                    invoiceId: (int) $row['invoice_id'],
                    allocatedAmount: (string) $row['allocated_amount'],
                    allocationDate: isset($row['allocation_date']) ? (string) $row['allocation_date'] : null,
                    allocationMethod: isset($row['allocation_method']) ? (string) $row['allocation_method'] : null,
                ),
                (array) ($validated['allocations'] ?? []),
            ),
            idempotencyKey: PaymentIdempotencyKey::fromRequest($this),
        );
    }
}
