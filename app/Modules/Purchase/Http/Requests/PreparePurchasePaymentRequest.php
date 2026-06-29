<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Illuminate\Validation\Rule;

final class PreparePurchasePaymentRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'supplier_type' => ['nullable', Rule::in(['supplier'])],
            'supplier_id' => ['required', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.amount' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.payment_method_id' => ['required', 'integer', 'min:1'],
            'lines.*.reference' => ['nullable', 'string', 'max:150'],
            'lines.*.instrument_direction' => ['nullable', Rule::in(['issued'])],
            'lines.*.external_bank_name' => ['nullable', 'string', 'max:150'],
            'lines.*.external_bank_branch' => ['nullable', 'string', 'max:150'],
            'lines.*.instrument_number' => ['nullable', 'string', 'max:150'],
            'lines.*.instrument_date' => ['nullable', 'date'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
            'lines.*.source_account_id' => ['prohibited'],
            'lines.*.internal_bank_account_id' => ['prohibited'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', 'integer', 'min:1'],
            'allocations.*.allocated_amount' => ['required', 'decimal:0,6', 'gt:0'],
            'allocations.*.allocation_date' => ['nullable', 'date'],
        ]);
    }
}
