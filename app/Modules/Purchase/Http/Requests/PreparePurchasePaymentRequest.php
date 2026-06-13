<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

final class PreparePurchasePaymentRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'supplier_type' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', 'integer', 'min:1'],
            'allocations.*.allocated_amount' => ['required', 'decimal:0,6', 'gt:0'],
            'allocations.*.allocation_date' => ['nullable', 'date'],
        ]);
    }
}
