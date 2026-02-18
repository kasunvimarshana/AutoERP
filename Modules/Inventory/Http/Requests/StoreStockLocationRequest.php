<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'parent_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
            'code' => ['required', 'string', 'max:50', 'unique:stock_locations,code'],
            'name' => ['required', 'string', 'max:255'],
            'location_type' => ['required', 'in:zone,aisle,rack,shelf,bin'],
            'aisle' => ['nullable', 'string', 'max:20'],
            'rack' => ['nullable', 'string', 'max:20'],
            'shelf' => ['nullable', 'string', 'max:20'],
            'bin' => ['nullable', 'string', 'max:20'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'capacity_unit' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }
}
