<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Requests;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertWarehouseLocationRequest extends TenantScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];
        $tenantId = $this->tenantId();
        $locationId = $this->route('warehouse_location');
        $warehouseId = (int) ($this->input('warehouse_id')
            ?? DB::table('warehouse_locations')
                ->where('id', $locationId)
                ->where('tenant_id', $tenantId)
                ->value('warehouse_id'));

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId),
            ],
            'row_version' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'warehouse_id' => array_merge($required, [
                'integer',
                'min:1',
                Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId),
            ]),
            'parent_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('warehouse_locations', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('warehouse_id', $warehouseId),
            ],
            'name' => array_merge($required, [
                'string',
                'max:255',
                Rule::unique('warehouse_locations', 'name')
                    ->where('tenant_id', $tenantId)
                    ->where('warehouse_id', $warehouseId)
                    ->ignore($locationId),
            ]),
            'code' => ['nullable', 'string', 'max:50'],
            'path' => ['nullable', 'string', 'max:2048'],
            'depth' => ['nullable', 'integer', 'min:0'],
            'type' => ['nullable', Rule::in(['zone', 'aisle', 'rack', 'shelf', 'bin', 'staging', 'dispatch'])],
            'is_active' => ['nullable', 'boolean'],
            'is_pickable' => ['nullable', 'boolean'],
            'is_receivable' => ['nullable', 'boolean'],
            'capacity' => ['nullable', 'decimal:0,4', 'gte:0'],
        ];
    }
}
