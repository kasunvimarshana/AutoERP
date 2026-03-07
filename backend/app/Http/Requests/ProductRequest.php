<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product') ?? $this->route('id');

        return [
            'category_id'         => ['nullable', 'integer', 'exists:product_categories,id'],
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'sku'                 => ['required', 'string', 'max:100', 'unique:products,sku,' . $productId],
            'barcode'             => ['nullable', 'string', 'max:100'],
            'price'               => ['required', 'numeric', 'min:0'],
            'cost'                => ['nullable', 'numeric', 'min:0'],
            'weight'              => ['nullable', 'numeric', 'min:0'],
            'dimensions'          => ['nullable', 'array'],
            'dimensions.length'   => ['nullable', 'numeric'],
            'dimensions.width'    => ['nullable', 'numeric'],
            'dimensions.height'   => ['nullable', 'numeric'],
            'status'              => ['nullable', 'in:active,inactive,discontinued'],
            'is_trackable'        => ['boolean'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'metadata'            => ['nullable', 'array'],
            'images'              => ['nullable', 'array'],
            'images.*'            => ['url'],
        ];
    }
}
