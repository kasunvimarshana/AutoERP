<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Product Request
 */
class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'   => ['sometimes', 'nullable', 'integer'],
            'sku'           => ['sometimes', 'string', 'max:100'],
            'name'          => ['sometimes', 'string', 'max:255'],
            'description'   => ['sometimes', 'nullable', 'string'],
            'price'         => ['sometimes', 'numeric', 'min:0'],
            'cost_price'    => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'quantity'      => ['sometimes', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'unit'          => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_active'     => ['sometimes', 'boolean'],
            'metadata'      => ['sometimes', 'array'],
        ];
    }
}
