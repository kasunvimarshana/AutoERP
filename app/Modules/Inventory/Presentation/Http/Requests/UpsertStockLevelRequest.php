<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertStockLevelRequest extends FormRequest
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
            'tenant_id' => [...$required, 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => [...$required, 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'serial_id' => ['nullable', 'integer', 'min:1'],
            'base_uom_id' => [...$required, 'integer', 'min:1'],
            'quantity_on_hand' => ['nullable', 'numeric', 'gte:0'],
            'quantity_reserved' => ['nullable', 'numeric', 'gte:0'],
            'quantity_blocked' => ['nullable', 'numeric', 'gte:0'],
            'quantity_damaged' => ['nullable', 'numeric', 'gte:0'],
            'quantity_in_transit' => ['nullable', 'numeric', 'gte:0'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'last_movement_at' => ['nullable', 'date'],
            'condition' => ['nullable', 'string', 'max:50'],
            'minimum_quantity' => ['nullable', 'numeric', 'gte:0'],
            'maximum_quantity' => ['nullable', 'numeric', 'gte:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'gte:0'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
