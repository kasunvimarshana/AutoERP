<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'organization_unit_id' => 'nullable|integer|exists:organization_units,id',
            'item_id' => 'required|integer|exists:items,id',
            'required_quantity' => 'required|numeric|min:0.0001',
            'location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'allocation_method' => 'nullable|string|in:QUANTITY,BATCH,LOT',
            'preferred_batch_ids' => 'nullable|array',
            'preferred_batch_ids.*' => 'integer|exists:batches,id',
            'preferred_lot_numbers' => 'nullable|array',
            'preferred_lot_numbers.*' => 'string|max:255',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
            'persist_reservation' => 'nullable|boolean',
            'expires_at' => 'nullable|date',
            'metadata' => 'nullable|array',
            'rule_context' => 'nullable|array',
            'rule_keys' => 'nullable|array',
            'rule_keys.*' => 'string|max:100',
        ];
    }
}
