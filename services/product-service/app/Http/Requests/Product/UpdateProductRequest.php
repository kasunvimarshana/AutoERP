<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateProductRequest — validates PUT/PATCH /api/products/{id}
 */
class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'string', 'uuid'],
            'name'        => ['sometimes', 'string', 'max:255'],
            'code'        => ['sometimes', 'string', 'max:100'],
            'sku'         => ['sometimes', 'string', 'max:100'],
            'price'       => ['sometimes', 'numeric', 'min:0'],
            'cost_price'  => ['sometimes', 'numeric', 'min:0'],
            'currency'    => ['sometimes', 'string', 'size:3'],
            'barcode'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'unit'        => ['sometimes', 'nullable', 'string', 'max:50'],
            'weight'      => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'dimensions'  => ['sometimes', 'array'],
            'status'      => ['sometimes', 'string', 'in:active,inactive,draft,discontinued'],
            'attributes'  => ['sometimes', 'array'],
            'metadata'    => ['sometimes', 'array'],
            'image_url'   => ['sometimes', 'nullable', 'url'],
            'is_trackable'=> ['sometimes', 'boolean'],
        ];
    }
}
