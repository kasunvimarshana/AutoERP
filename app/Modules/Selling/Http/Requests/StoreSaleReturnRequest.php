<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class StoreSaleReturnRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'return_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:1000'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sale_line_id' => ['required', 'integer', 'min:1', 'distinct'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return array_merge($this->validated(), [
            'tenant_id' => $this->tenantId(),
            'organization_unit_id' => $this->organizationUnitId(),
            'sale_id' => (int) $this->route('sale'),
            'current_user_id' => $this->currentUserId(),
        ]);
    }
}
