<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'serial_id' => 'nullable|integer|exists:serials,id',
            'location_id' => 'required|integer|exists:warehouse_locations,id',
            'quantity' => 'required|numeric|min:0.000001',
            'reserved_for_type' => 'nullable|string|max:255',
            'reserved_for_id' => 'nullable|integer',
            'expires_at' => 'nullable|date',
        ];
    }
}
