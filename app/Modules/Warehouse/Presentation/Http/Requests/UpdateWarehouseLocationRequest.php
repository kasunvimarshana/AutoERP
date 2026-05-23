<?php

declare(strict_types=1);

namespace Modules\Warehouse\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'parent_id' => ['nullable', 'integer', 'exists:warehouse_locations,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouse_locations', 'name')
                    ->where('tenant_id', $this->route('tenant'))
                    ->where('warehouse_id', $this->route('warehouse'))
                    ->ignore($this->route('location')),
            ],
            'code' => ['nullable', 'string', 'max:255'],
            'path' => ['nullable', 'string', 'max:255'],
            'depth' => ['nullable', 'integer', 'min:0'],
            'type' => ['nullable', 'string', 'in:zone,aisle,rack,shelf,bin,staging,dispatch'],
            'is_active' => ['nullable', 'boolean'],
            'is_pickable' => ['nullable', 'boolean'],
            'is_receivable' => ['nullable', 'boolean'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
