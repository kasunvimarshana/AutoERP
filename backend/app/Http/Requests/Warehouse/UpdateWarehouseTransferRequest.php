<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('warehouse_transfer');
        
        return [
            'transfer_number' => ['nullable', 'string', 'max:50', "unique:warehouse_transfers,transfer_number,{$id}"],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'source_branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'source_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'destination_branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'destination_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'status' => ['nullable', 'in:draft,pending,approved,in_transit,received,cancelled'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'transfer_date' => ['sometimes', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'handling_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'carrier' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity_requested' => ['required', 'numeric', 'min:0.0001', 'max:999999999.9999'],
            'items.*.unit_id' => ['required', 'integer', 'exists:units_of_measure,id'],
            'items.*.batch_number' => ['nullable', 'string', 'max:50'],
            'items.*.serial_number' => ['nullable', 'string', 'max:50'],
            'items.*.lot_number' => ['nullable', 'string', 'max:50'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
