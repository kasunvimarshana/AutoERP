<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
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
            'payment_type' => ['required', Rule::enum(PaymentType::class)],
            'direction' => ['required', Rule::enum(PaymentDirection::class)],
            'payment_date' => ['required', 'date'],
            'payment_number' => ['nullable', 'string', 'max:100'],
            'party_type' => ['nullable', 'string', 'max:150'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'source_type' => ['nullable', 'string', 'max:150'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'allocation_status' => ['nullable', 'string', 'max:50'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'cheque_number' => ['nullable', 'string', 'max:100'],
            'cheque_date' => ['nullable', 'date'],
            'bank_account_id' => [
                'nullable',
                'integer',
                $this->tenantExists('finance_accounts', 'id')
                    ->where('is_bank_account', true),
            ],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'amount_in_words' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            ...$this->paymentLineRules(),
            ...$this->paymentAllocationRules(['nullable', 'array']),
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
            paymentNumber: $this->stringOrNull('payment_number'),
            partyType: $this->stringOrNull('party_type'),
            partyId: $this->intOrNull('party_id'),
            sourceType: $this->stringOrNull('source_type'),
            sourceId: $this->intOrNull('source_id'),
            allocationStatus: (string) $this->input('allocation_status', 'unallocated'),
            currencyId: $this->intOrNull('currency_id'),
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            referenceNumber: $this->stringOrNull('reference_number'),
            chequeNumber: $this->stringOrNull('cheque_number'),
            chequeDate: $this->stringOrNull('cheque_date'),
            bankAccountId: $this->intOrNull('bank_account_id'),
            payeeName: $this->stringOrNull('payee_name'),
            amountInWords: $this->stringOrNull('amount_in_words'),
            notes: $this->stringOrNull('notes'),
            createdBy: $this->currentUserId(),
            lines: $this->paymentLineData(),
            allocations: $this->paymentAllocationData(),
            metadata: $this->input('metadata'),
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private function paymentLineRules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.payment_method_id' => ['required', 'integer', 'min:1'],
            'lines.*.reference_number' => ['nullable', 'string', 'max:150'],
            'lines.*.amount' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.cleared_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.status' => ['nullable', 'string', 'max:50'],
            'lines.*.internal_bank_account_id' => [
                'nullable',
                'integer',
                $this->tenantExists('finance_accounts', 'id')
                    ->where('is_bank_account', true),
            ],
            'lines.*.instrument_direction' => ['nullable', Rule::in(['received', 'issued'])],
            'lines.*.external_bank_name' => ['nullable', 'string', 'max:150'],
            'lines.*.external_bank_branch' => ['nullable', 'string', 'max:150'],
            'lines.*.instrument_number' => ['nullable', 'string', 'max:150'],
            'lines.*.instrument_date' => ['nullable', 'date'],
            'lines.*.deposit_date' => ['nullable', 'date'],
            'lines.*.realized_date' => ['nullable', 'date'],
            'lines.*.clearing_date' => ['nullable', 'date'],
            'lines.*.bounced_date' => ['nullable', 'date'],
            'lines.*.returned_date' => ['nullable', 'date'],
            'lines.*.cancellation_reason' => ['nullable', 'string', 'max:1000'],
            'lines.*.notes' => ['nullable', 'string'],
            'lines.*.metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * @return list<PaymentLineData>
     */
    private function paymentLineData(): array
    {
        return array_map(static fn (array $row): PaymentLineData => new PaymentLineData(
            amount: (string) $row['amount'],
            paymentMethodId: isset($row['payment_method_id']) ? (int) $row['payment_method_id'] : null,
            referenceNumber: $row['reference_number'] ?? null,
            clearedAmount: (string) ($row['cleared_amount'] ?? '0.000000'),
            status: (string) ($row['status'] ?? 'pending'),
            notes: $row['notes'] ?? null,
            metadata: isset($row['metadata']) && is_array($row['metadata']) ? $row['metadata'] : null,
            internalBankAccountId: isset($row['internal_bank_account_id']) ? (int) $row['internal_bank_account_id'] : null,
            instrumentDirection: $row['instrument_direction'] ?? null,
            externalBankName: $row['external_bank_name'] ?? null,
            externalBankBranch: $row['external_bank_branch'] ?? null,
            instrumentNumber: $row['instrument_number'] ?? null,
            instrumentDate: $row['instrument_date'] ?? null,
            depositDate: $row['deposit_date'] ?? null,
            realizedDate: $row['realized_date'] ?? null,
            clearingDate: $row['clearing_date'] ?? null,
            bouncedDate: $row['bounced_date'] ?? null,
            returnedDate: $row['returned_date'] ?? null,
            cancellationReason: $row['cancellation_reason'] ?? null,
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
