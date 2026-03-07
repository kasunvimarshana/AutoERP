<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product') ?? $this->route('id');

        return [
            'name'             => ['sometimes', 'string', 'max:255'],
            'sku'              => ['sometimes', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'category'         => ['sometimes', 'string', 'max:100'],
            'price'            => ['sometimes', 'numeric', 'min:0'],
            'cost'             => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity'   => ['sometimes', 'integer', 'min:0'],
            'reorder_point'    => ['sometimes', 'integer', 'min:0'],
            'reorder_quantity' => ['sometimes', 'integer', 'min:0'],
            'description'      => ['sometimes', 'nullable', 'string'],
            'unit'             => ['sometimes', 'nullable', 'string', 'max:50'],
            'weight'           => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'dimensions'       => ['sometimes', 'nullable', 'array'],
            'attributes'       => ['sometimes', 'nullable', 'array'],
            'is_active'        => ['sometimes', 'boolean'],
            'is_trackable'     => ['sometimes', 'boolean'],
        ];
    }
}
