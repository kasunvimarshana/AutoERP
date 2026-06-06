<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payment\DTOs\PaymentAllocationData;

final class AllocatePaymentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'integer', 'min:1'],
            'allocations.*.allocated_amount' => ['required', 'decimal:0,6', 'gt:0'],
            'allocations.*.allocation_date' => ['required', 'date'],
            'allocations.*.allow_overpayment' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): array
    {
        return array_map(static fn (array $row): PaymentAllocationData => new PaymentAllocationData(
            invoiceId: (int) $row['invoice_id'],
            allocatedAmount: (string) $row['allocated_amount'],
            allocationDate: (string) $row['allocation_date'],
            allowOverpayment: (bool) ($row['allow_overpayment'] ?? false),
        ), $this->input('allocations'));
    }
}
