<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertInventoryCostLayerRequest extends FormRequest
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
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'serial_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'valuation_method' => ['nullable', 'string', 'max:50'],
            'layer_date' => [...$required, 'date'],
            'quantity_in' => [...$required, 'numeric', 'gt:0'],
            'quantity_remaining' => [...$required, 'numeric', 'gte:0'],
            'unit_cost' => [...$required, 'numeric', 'gte:0'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'is_closed' => ['nullable', 'boolean'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
