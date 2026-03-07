<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // the ID is injected from the URI
        $productId = $this->route('product');

        return [
            'sku' => [
                'sometimes',
                'string',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
        ];
    }
}
