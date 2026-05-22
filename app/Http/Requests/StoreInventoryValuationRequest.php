<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryValuationRequest extends FormRequest
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
            'location_id' => 'required|integer|exists:warehouse_locations,id',
            'uom_id' => 'required|integer|exists:unit_of_measures,id',
            'direction' => 'required|string|in:IN,OUT,in,out',
            'quantity' => 'required|numeric|min:0.0001',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'serial_id' => 'nullable|integer|exists:serials,id',
            'unit_cost' => 'nullable|numeric|min:0',
            'txn_type' => 'nullable|string|max:100',
            'performed_by' => 'nullable|integer|exists:users,id',
            'performed_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
            'valuation_method' => 'nullable|string|in:FIFO,LIFO,WEIGHTED_AVERAGE',
            'layer_date' => 'nullable|date',
        ];
    }
}
