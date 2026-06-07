<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertWarehouseRequest extends TenantScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];
        $tenantId = $this->tenantId();
        $warehouseId = $this->route('warehouse');

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
            'name' => array_merge($required, [
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')->where('tenant_id', $tenantId)->ignore($warehouseId),
            ]),
            'code' => ['nullable', 'string', 'max:50'],
            'image_path' => ['nullable', 'string', 'max:2048'],
            'type' => ['nullable', Rule::in(['standard', 'virtual', 'transit', 'quarantine'])],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
