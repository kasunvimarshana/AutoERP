<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListStockLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'item_id' => ['nullable', 'integer', 'min:1', 'exists:items,id'],
            'warehouse_id' => ['nullable', 'integer', 'min:1', 'exists:warehouses,id'],
            'location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'base_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'batch_id' => ['nullable', 'integer', 'min:1', 'exists:batches,id'],
            'serial_id' => ['nullable', 'integer', 'min:1', 'exists:serials,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'condition' => ['nullable', 'string', 'max:50'],
            'batch_serial' => ['nullable', 'string', 'max:120'],
            'search' => ['nullable', 'string', 'max:120'],
            'low_stock' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('inventory.pagination.max_per_page', 200)],
        ];
    }
}
