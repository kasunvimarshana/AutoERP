<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy in controller.
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'sku'              => ['required', 'string', 'max:100', 'unique:products,sku'],
            'category'         => ['required', 'string', 'max:100'],
            'price'            => ['required', 'numeric', 'min:0'],
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
