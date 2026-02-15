<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehousePutawayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'putaway_number' => ['nullable', 'string', 'max:50', 'unique:warehouse_putaways,putaway_number'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id' => ['nullable', 'integer'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,assigned,in_progress,completed,cancelled'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'putaway_type' => ['nullable', 'in:purchase,transfer,return,other'],
            'scheduled_date' => ['required', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'receiving_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'notes' => ['nullable', 'string'],
            
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.destination_location_id' => ['required', 'integer', 'exists:locations,id'],
            'items.*.quantity_to_putaway' => ['required', 'numeric', 'min:0.0001', 'max:999999999.9999'],
            'items.*.unit_id' => ['required', 'integer', 'exists:units_of_measure,id'],
            'items.*.batch_number' => ['nullable', 'string', 'max:50'],
            'items.*.serial_number' => ['nullable', 'string', 'max:50'],
            'items.*.lot_number' => ['nullable', 'string', 'max:50'],
            'items.*.manufacture_date' => ['nullable', 'date'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'items.*.sequence' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
