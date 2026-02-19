<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('products.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', new Enum(ProductType::class)],
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:100'],
            'category_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_categories,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_purchasable' => ['sometimes', 'boolean'],
            'is_saleable' => ['sometimes', 'boolean'],
            'is_trackable' => ['sometimes', 'boolean'],
            'buy_unit_id' => ['sometimes', 'nullable', 'uuid', 'exists:units,id'],
            'sell_unit_id' => ['sometimes', 'nullable', 'uuid', 'exists:units,id'],
            'buy_unit_cost' => ['sometimes', 'numeric', 'min:0'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'attributes' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
            'lock_version' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
