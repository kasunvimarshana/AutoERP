<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'variant_id' => ['nullable', 'integer', 'exists:item_variants,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'location_id' => ['nullable', 'integer', 'exists:warehouse_locations,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'serial_id' => ['nullable', 'integer', 'exists:serials,id'],
            'uom_id' => ['required', 'integer', 'exists:unit_of_measures,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'direction' => ['required', 'string', 'in:IN,OUT'],
            'txn_type' => ['required', 'string', 'max:120'],
            'provided_unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'performed_by' => ['nullable', 'integer', 'exists:users,id'],
            'reference_type' => ['nullable', 'string', 'max:190'],
            'reference_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
