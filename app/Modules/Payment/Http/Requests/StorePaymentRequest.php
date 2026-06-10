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
            'payment_number' => ['nullable', 'string', 'max:100'],
            'party_type' => ['nullable', 'string', 'max:150'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'cheque_number' => ['nullable', 'string', 'max:100'],
            'cheque_date' => ['nullable', 'date'],
            'bank_account_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_accounts', 'id')
                    ->where('tenant_id', $this->tenantId())
                    ->where('is_bank_account', true),
            ],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'amount_in_words' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.payment_method_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.amount' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.cleared_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', 'integer', 'min:1'],
            'allocations.*.allocated_amount' => ['required', 'decimal:0,6', 'gt:0'],
            'allocations.*.allocation_date' => ['required', 'date'],
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
            lines: array_map(static fn (array $row): PaymentLineData => new PaymentLineData(
                amount: (string) $row['amount'],
                paymentMethodId: isset($row['payment_method_id']) ? (int) $row['payment_method_id'] : null,
                referenceNumber: $row['reference_number'] ?? null,
                clearedAmount: (string) ($row['cleared_amount'] ?? '0.000000'),
                status: (string) ($row['status'] ?? 'pending'),
                notes: $row['notes'] ?? null,
            ), $this->input('lines')),
            allocations: array_map(static fn (array $row): PaymentAllocationData => new PaymentAllocationData(
                invoiceId: (int) $row['invoice_id'],
                allocatedAmount: (string) $row['allocated_amount'],
                allocationDate: (string) $row['allocation_date'],
                allowOverpayment: (bool) ($row['allow_overpayment'] ?? false),
            ), $this->input('allocations', [])),
        );
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
