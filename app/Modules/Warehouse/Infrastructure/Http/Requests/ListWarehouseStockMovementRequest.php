<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListWarehouseStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'movement_type' => 'nullable|in:receipt,shipment,transfer,adjustment,adjustment_in,adjustment_out,opening,return_in,return_out,reservation,reservation_release,write_off,cycle_count',
            'from_location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'to_location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:64',
        ];
    }
}
