<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateProductRequest — validates POST /api/products
 */
class CreateProductRequest extends FormRequest
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
            'category_id' => ['required', 'string', 'uuid'],
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:100'],
            'sku'         => ['required', 'string', 'max:100'],
            'price'       => ['required', 'numeric', 'min:0'],
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
