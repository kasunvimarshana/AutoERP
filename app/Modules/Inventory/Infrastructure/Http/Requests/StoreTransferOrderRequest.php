<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'org_unit_id' => 'nullable|integer|exists:org_units,id',
            'from_warehouse_id' => 'required|integer|exists:warehouses,id',
            'to_warehouse_id' => 'required|integer|exists:warehouses,id|different:from_warehouse_id',
            'transfer_number' => 'required|string|max:255',
            'status' => 'nullable|in:draft,approved,in_transit,received,cancelled',
            'request_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer|exists:products,id',
            'lines.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'lines.*.batch_id' => 'nullable|integer|exists:batches,id',
            'lines.*.serial_id' => 'nullable|integer|exists:serials,id',
            'lines.*.from_location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'lines.*.to_location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'lines.*.uom_id' => 'required|integer|exists:units_of_measure,id',
            'lines.*.requested_qty' => 'required|numeric|min:0.000001',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
        ];
    }
}
