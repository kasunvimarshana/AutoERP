<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|max:100',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'cost' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|nullable|integer',
            'weight' => 'sometimes|numeric|min:0',
            'dimensions' => 'sometimes|array',
            'images' => 'sometimes|array',
            'tags' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
