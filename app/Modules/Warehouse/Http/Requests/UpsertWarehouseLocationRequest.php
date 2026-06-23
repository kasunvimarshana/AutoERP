<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertWarehouseLocationRequest extends TenantScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => [
                'nullable',
                'integer',
                'min:1',
                $this->tenantExists('organization_units', 'id'),
            ],
            'row_version' => [$this->isMethod('post') ? 'nullable' : 'required', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'warehouse_id' => array_merge($required, [
                'integer',
                'min:1',
                $this->tenantExists('warehouses', 'id'),
            ]),
            'parent_id' => [
                'nullable',
                'integer',
                'min:1',
                $this->tenantExists('warehouse_locations', 'id'),
            ],
            'name' => array_merge($required, [
                'string',
                'max:255',
            ]),
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', Rule::in(['zone', 'aisle', 'rack', 'shelf', 'bin', 'staging', 'dispatch'])],
            'is_active' => ['nullable', 'boolean'],
            'is_pickable' => ['nullable', 'boolean'],
            'is_receivable' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'capacity' => ['nullable', 'decimal:0,6', 'gte:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('is_default') && $this->has('is_active') && ! $this->boolean('is_active')) {
                $validator->errors()->add('is_default', 'Default warehouse location must be active.');
            }
        });
    }
}
