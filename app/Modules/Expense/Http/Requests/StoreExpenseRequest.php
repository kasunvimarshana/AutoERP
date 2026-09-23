<?php

declare(strict_types=1);

namespace Modules\Expense\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Expense\Constants\ExpenseIdempotency;
use Modules\Expense\DTOs\CreateExpenseData;

final class StoreExpenseRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['required', 'integer', 'min:1'],
            ExpenseIdempotency::REQUEST_ATTRIBUTE => ['required', 'string', 'max:'.ExpenseIdempotency::MAX_KEY_LENGTH],
            'expense_type_id' => ['required', 'integer', $this->tenantExists('expense_types')],
            'payment_method_id' => ['required', 'integer', 'min:1'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')->where('is_active', true)],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'instrument_number' => ['nullable', 'string', 'max:150'],
            'instrument_date' => ['nullable', 'date'],
            'external_bank_name' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'expense_number' => ['prohibited'],
            'status' => ['prohibited'],
            'finance_posting_reference' => ['prohibited'],
        ];
    }

    public function toData(): CreateExpenseData
    {
        $organizationUnitId = $this->organizationUnitId();
        if ($organizationUnitId === null) {
            throw new \LogicException('Expense entry requires an active organization unit.');
        }

        return new CreateExpenseData(
            tenantId: $this->tenantId(),
            organizationUnitId: $organizationUnitId,
            expenseTypeId: (int) $this->input('expense_type_id'),
            paymentMethodId: (int) $this->input('payment_method_id'),
            expenseDate: (string) $this->input('expense_date'),
            amount: (string) $this->input('amount'),
            currencyId: $this->filled('currency_id') ? (int) $this->input('currency_id') : null,
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            referenceNumber: $this->stringOrNull('reference_number'),
            instrumentNumber: $this->stringOrNull('instrument_number'),
            instrumentDate: $this->stringOrNull('instrument_date'),
            externalBankName: $this->stringOrNull('external_bank_name'),
            notes: $this->stringOrNull('notes'),
            createdBy: $this->currentUserId(),
            idempotencyKey: (string) $this->input(ExpenseIdempotency::REQUEST_ATTRIBUTE),
        );
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $header = $this->header(ExpenseIdempotency::REQUEST_HEADER);
        $this->merge([
            ExpenseIdempotency::REQUEST_ATTRIBUTE => is_string($header) ? trim($header) : null,
        ]);
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? trim((string) $this->input($key)) : null;
    }
}
