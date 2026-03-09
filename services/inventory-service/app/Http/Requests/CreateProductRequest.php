<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Product Request
 */
class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'   => ['nullable', 'integer'],
            'sku'           => ['required', 'string', 'max:100'],
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'price'         => ['required', 'numeric', 'min:0'],
            'cost_price'    => ['nullable', 'numeric', 'min:0'],
            'quantity'      => ['required', 'integer', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'unit'          => ['nullable', 'string', 'max:50'],
            'is_active'     => ['sometimes', 'boolean'],
            'metadata'      => ['sometimes', 'array'],
        ];
    }
}
