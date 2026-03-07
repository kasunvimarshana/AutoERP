<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'description' => 'sometimes|string',
            'price' => 'required|numeric|min:0',
            'cost' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|nullable|integer',
            'weight' => 'sometimes|numeric|min:0',
            'dimensions' => 'sometimes|array',
            'images' => 'sometimes|array',
            'images.*' => 'url',
            'tags' => 'sometimes|array',
            'tags.*' => 'string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
