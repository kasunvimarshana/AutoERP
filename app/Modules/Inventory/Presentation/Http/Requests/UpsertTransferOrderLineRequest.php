<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertTransferOrderLineRequest extends FormRequest
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
            'transfer_order_id' => array_merge($required, ['integer', 'min:1', 'exists:transfer_orders,id']),
            'item_id' => array_merge($required, ['integer', 'min:1', 'exists:items,id']),
            'variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'batch_id' => ['nullable', 'integer', 'min:1', 'exists:batches,id'],
            'serial_id' => ['nullable', 'integer', 'min:1', 'exists:serials,id'],
            'from_location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'to_location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'requested_qty' => array_merge($required, ['numeric']),
            'shipped_qty' => ['nullable', 'numeric'],
            'received_qty' => ['nullable', 'numeric'],
            'unit_cost' => ['nullable', 'numeric'],
        ];
    }
}