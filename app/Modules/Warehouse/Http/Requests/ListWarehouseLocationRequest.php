<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ListWarehouseLocationRequest extends TenantScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('warehouse.pagination.max_per_page', 200)],
        ];
    }
}
