<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertCycleCountLineRequest extends FormRequest
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
            'count_header_id' => array_merge($required, ['integer', 'min:1', 'exists:cycle_count_headers,id']),
            'item_id' => array_merge($required, ['integer', 'min:1', 'exists:items,id']),
            'variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'batch_id' => ['nullable', 'integer', 'min:1', 'exists:batches,id'],
            'serial_id' => ['nullable', 'integer', 'min:1', 'exists:serials,id'],
            'location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'system_qty' => array_merge($required, ['numeric']),
            'counted_qty' => array_merge($required, ['numeric']),
            'variance_qty' => array_merge($required, ['numeric']),
            'unit_cost' => array_merge($required, ['numeric']),
            'variance_value' => array_merge($required, ['numeric']),
            'adjustment_movement_id' => ['nullable', 'integer', 'min:1', 'exists:stock_movements,id'],
        ];
    }
}