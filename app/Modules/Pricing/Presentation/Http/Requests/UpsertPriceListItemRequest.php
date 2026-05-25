<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPriceListItemRequest extends FormRequest
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
            'price_list_id' => array_merge($required, ['integer', 'min:1', 'exists:price_lists,id']),
            'item_id' => array_merge($required, ['integer', 'min:1', 'exists:items,id']),
            'variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'warehouse_id' => ['nullable', 'integer', 'min:1', 'exists:warehouses,id'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'batch_id' => ['nullable', 'integer', 'min:1', 'exists:batches,id'],
            'serial_id' => ['nullable', 'integer', 'min:1', 'exists:serials,id'],
            'uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'min_quantity' => ['nullable', 'numeric'],
            'price' => array_merge($required, ['numeric']),
            'discount_type' => ['nullable', 'string', 'max:255'],
            'discount_value' => ['nullable', 'numeric'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date']
        ];
    }
}