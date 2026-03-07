<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku'             => ['sometimes', 'string', 'max:100'],
            'name'            => ['sometimes', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'category_id'     => ['nullable', 'integer', 'exists:categories,id'],
            'price'           => ['sometimes', 'numeric', 'min:0'],
            'cost_price'      => ['nullable', 'numeric', 'min:0'],
            'unit'            => ['nullable', 'string', 'max:50'],
            'weight'          => ['nullable', 'numeric', 'min:0'],
            'dimensions'      => ['nullable', 'array'],
            'dimensions.length' => ['nullable', 'numeric', 'min:0'],
            'dimensions.width'  => ['nullable', 'numeric', 'min:0'],
            'dimensions.height' => ['nullable', 'numeric', 'min:0'],
            'dimensions.unit'   => ['nullable', 'string', 'max:10'],
            'images'          => ['nullable', 'array'],
            'images.*'        => ['string', 'url'],
            'attributes'      => ['nullable', 'array'],
            'is_active'       => ['nullable', 'boolean'],
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'max_stock_level' => ['nullable', 'integer', 'min:0'],
            'reorder_point'   => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.min' => 'Price must be zero or greater.',
        ];
    }
}
