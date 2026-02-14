<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\BaseRequest;

/**
 * UpdateProductRequest
 * 
 * Form request for updating an existing product
 */
class UpdateProductRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $productId = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', "unique:products,slug,{$productId}"],
            'sku' => ['nullable', 'string', 'max:100', "unique:products,sku,{$productId}"],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'required', 'string', 'in:simple,variant,service'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'is_variant_product' => ['boolean'],
            'track_stock' => ['boolean'],
            'track_batch' => ['boolean'],
            'track_serial' => ['boolean'],
            'min_stock_level' => ['nullable', 'numeric', 'min:0'],
            'max_stock_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_inclusive' => ['boolean'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'attributes' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'is_purchasable' => ['boolean'],
            'is_sellable' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'type.required' => 'Product type is required',
            'type.in' => 'Product type must be one of: simple, variant, service',
            'unit.required' => 'Unit of measurement is required',
            'sku.unique' => 'This SKU is already in use',
            'slug.unique' => 'This slug is already in use',
        ];
    }
}
