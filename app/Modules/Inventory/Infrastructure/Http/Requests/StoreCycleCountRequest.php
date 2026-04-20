<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCycleCountRequest extends FormRequest
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
            'location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'counted_by_user_id' => 'nullable|integer|exists:users,id',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer|exists:products,id',
            'lines.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'lines.*.batch_id' => 'nullable|integer|exists:batches,id',
            'lines.*.serial_id' => 'nullable|integer|exists:serials,id',
            'lines.*.counted_qty' => 'nullable|numeric|min:0',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
        ];
    }
}
