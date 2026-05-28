<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertStockAdjustmentLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
            'stock_adjustment_id' => [...$required, 'integer', 'min:1'],
            'warehouse_id' => [...$required, 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'serial_id' => ['nullable', 'integer', 'min:1'],
            'uom_id' => [...$required, 'integer', 'min:1'],
            'direction' => ['nullable', 'string', 'max:20'],
            'current_quantity' => ['nullable', 'numeric'],
            'adjustment_quantity' => [...$required, 'numeric', 'not_in:0'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'reason_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
