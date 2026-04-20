<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Requests;

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
        $tenantId = (int) $this->input('tenant_id');
        $warehouseId = (int) $this->route('warehouse');
        $locationId = (int) $this->route('location');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouse_locations', 'id')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('warehouse_id', $warehouseId)),
                Rule::notIn([$locationId]),
            ],
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('warehouse_locations', 'code')
                    ->ignore($locationId)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('warehouse_id', $warehouseId)),
            ],
            'type' => 'nullable|in:zone,aisle,rack,shelf,bin,staging,dispatch',
            'is_active' => 'nullable|boolean',
            'is_pickable' => 'nullable|boolean',
            'is_receivable' => 'nullable|boolean',
            'capacity' => 'nullable|numeric|min:0',
            'metadata' => 'nullable|array',
        ];
    }
}
