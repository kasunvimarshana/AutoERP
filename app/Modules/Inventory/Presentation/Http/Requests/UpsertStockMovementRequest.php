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
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'direction' => array_merge($required, ['string', 'max:255']),
            'item_id' => array_merge($required, ['integer', 'min:1', 'exists:items,id']),
            'variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'batch_id' => ['nullable', 'integer', 'min:1', 'exists:batches,id'],
            'serial_id' => ['nullable', 'integer', 'min:1', 'exists:serials,id'],
            'location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'warehouse_id' => ['nullable', 'integer', 'min:1', 'exists:warehouses,id'],
            'txn_type' => array_merge($required, ['string', 'max:255']),
            'uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'quantity' => ['nullable', 'numeric'],
            'quantity_in' => ['nullable', 'numeric'],
            'quantity_out' => ['nullable', 'numeric'],
            'total_cost' => ['nullable', 'numeric'],
            'balance_quantity' => ['nullable', 'numeric'],
            'balance_value' => ['nullable', 'numeric'],
            'performed_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'performed_at' => array_merge($required, ['date']),
            'notes' => ['nullable', 'string'],
            'reference_type' => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'integer', 'min:0'],
        ];
    }
}