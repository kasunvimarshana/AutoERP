<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer',
            'product_sku' => 'required|string|max:100',
            'product_name' => 'required|string|max:255',
            'quantity' => 'nullable|integer|min:0',
            'reserved_quantity' => 'nullable|integer|min:0',
            'minimum_quantity' => 'nullable|integer|min:0',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:available,low_stock,out_of_stock,reserved',
            'metadata' => 'nullable|array',
        ];
    }
}
