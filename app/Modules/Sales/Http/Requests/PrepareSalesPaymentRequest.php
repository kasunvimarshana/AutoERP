<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class PrepareSalesPaymentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', 'integer', 'min:1'],
            'allocations.*.allocated_amount' => ['required', 'decimal:0,6', 'gt:0'],
            'allocations.*.allocation_date' => ['nullable', 'date'],
        ];
    }
}
