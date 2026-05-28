<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertStockMovementRequest extends FormRequest
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
            'direction' => [...$required, 'string', 'in:IN,OUT'],
            'movement_type' => [...$required, 'string', 'max:100'],
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'serial_id' => ['nullable', 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_line_id' => ['nullable', 'integer', 'min:1'],
            'transaction_uom_id' => [...$required, 'integer', 'min:1'],
            'base_uom_id' => [...$required, 'integer', 'min:1'],
            'quantity' => [...$required, 'numeric', 'gte:0'],
            'base_quantity' => [...$required, 'numeric', 'gte:0'],
            'quantity_in' => ['nullable', 'numeric', 'gte:0'],
            'quantity_out' => ['nullable', 'numeric', 'gte:0'],
            'base_quantity_in' => ['nullable', 'numeric', 'gte:0'],
            'base_quantity_out' => ['nullable', 'numeric', 'gte:0'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'total_cost' => ['nullable', 'numeric', 'gte:0'],
            'balance_quantity' => ['nullable', 'numeric'],
            'balance_value' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'max:50'],
            'performed_by' => ['nullable', 'integer', 'min:1'],
            'approved_by' => ['nullable', 'integer', 'min:1'],
            'reversed_movement_id' => ['nullable', 'integer', 'min:1'],
            'performed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
