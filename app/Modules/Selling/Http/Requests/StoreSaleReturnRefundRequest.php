<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class StoreSaleReturnRefundRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'refund_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'expected_refundable_amount' => ['required', 'decimal:0,6', 'gte:0'],
            'payment_method_id' => ['required', 'integer', 'min:1'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'external_bank_name' => ['nullable', 'string', 'max:255'],
            'instrument_number' => ['nullable', 'string', 'max:255'],
            'instrument_date' => ['nullable', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return array_merge($this->validated(), [
            'tenant_id' => $this->tenantId(),
            'organization_unit_id' => $this->organizationUnitId(),
            'return_id' => (int) $this->route('return'),
            'current_user_id' => $this->currentUserId(),
        ]);
    }
}
