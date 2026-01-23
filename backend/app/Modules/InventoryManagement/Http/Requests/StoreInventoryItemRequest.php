<?php

namespace App\Modules\InventoryManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_number' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'item_type' => 'required|in:part,consumable,service,labor,dummy',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'unit_of_measure' => 'required|string|max:50',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'markup_percentage' => 'nullable|numeric|min:0|max:100',
            'quantity_in_stock' => 'nullable|integer|min:0',
            'minimum_stock_level' => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:0',
            'location' => 'nullable|string|max:100',
            'is_taxable' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'is_dummy' => 'nullable|boolean',
        ];
    }
}
