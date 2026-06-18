<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Requests;

use Illuminate\Validation\Rule;
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
            'organization_unit_filter_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'type' => ['nullable', Rule::in(['zone', 'aisle', 'rack', 'shelf', 'bin', 'staging', 'dispatch'])],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('warehouse.pagination.max_per_page', 200)],
        ];
    }
}
