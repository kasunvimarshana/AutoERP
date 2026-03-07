<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku'             => ['required', 'string', 'max:100'],
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'category_id'     => ['nullable', 'integer', 'exists:categories,id'],
            'price'           => ['required', 'numeric', 'min:0'],
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
            'max_stock_level' => ['nullable', 'integer', 'min:0', 'gte:min_stock_level'],
            'reorder_point'   => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required'   => 'A SKU is required.',
            'name.required'  => 'A product name is required.',
            'price.required' => 'A price is required.',
            'price.min'      => 'Price must be zero or greater.',
            'max_stock_level.gte' => 'Max stock level must be greater than or equal to min stock level.',
        ];
    }
}
