<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\Enums\PaymentAllocationMethod;

trait BuildsPaymentAllocations
{
    /**
     * @param  list<string>  $rootRules
     * @return array<string, list<string>>
     */
    protected function paymentAllocationRules(array $rootRules): array
    {
        return [
            'allocations' => $rootRules,
            'allocations.*.invoice_id' => ['required', 'integer', 'min:1'],
            'allocations.*.allocated_amount' => ['required', 'decimal:0,6', 'gt:0'],
            'allocations.*.allocation_date' => ['required', 'date'],
            'allocations.*.allow_overpayment' => ['nullable', 'boolean'],
            'allocations.*.allocation_method' => ['nullable', Rule::enum(PaymentAllocationMethod::class)],
            'allocations.*.metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * @return list<PaymentAllocationData>
     */
    protected function paymentAllocationData(): array
    {
        $rows = $this->input('allocations', []);

        return array_map(static fn (array $row): PaymentAllocationData => new PaymentAllocationData(
            invoiceId: (int) $row['invoice_id'],
            allocatedAmount: (string) $row['allocated_amount'],
            allocationDate: (string) $row['allocation_date'],
            allowOverpayment: (bool) ($row['allow_overpayment'] ?? false),
            allocationMethod: (string) ($row['allocation_method'] ?? PaymentAllocationMethod::SpecificInvoice->value),
            metadata: isset($row['metadata']) && is_array($row['metadata']) ? $row['metadata'] : null,
        ), is_array($rows) ? $rows : []);
    }
}
