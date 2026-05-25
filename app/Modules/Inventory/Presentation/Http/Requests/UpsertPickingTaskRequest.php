<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPickingTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'receipt_inspection_id' => ['nullable', 'integer', 'min:1', 'exists:receipt_inspections,id'],
            'stock_movement_id' => array_merge($required, ['integer', 'min:1', 'exists:stock_movements,id']),
            'source_warehouse_id' => array_merge($required, ['integer', 'min:1', 'exists:warehouses,id']),
            'source_location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'quantity' => array_merge($required, ['numeric']),
            'status' => ['nullable', 'string', 'max:255'],
            'assigned_user_id' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}