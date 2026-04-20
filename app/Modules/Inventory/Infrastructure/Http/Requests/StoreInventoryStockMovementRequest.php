<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'serial_id' => 'nullable|integer|exists:serials,id',
            'from_location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'to_location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'movement_type' => 'required|in:receipt,shipment,transfer,adjustment,adjustment_in,adjustment_out,opening,return_in,return_out,reservation,reservation_release,write_off,cycle_count',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
            'uom_id' => 'required|integer|exists:units_of_measure,id',
            'quantity' => 'required|numeric|not_in:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'performed_by' => 'nullable|integer|exists:users,id',
            'performed_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
