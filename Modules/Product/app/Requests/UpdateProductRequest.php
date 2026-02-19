<?php

declare(strict_types=1);

namespace Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;

/**
 * Update Product Request
 *
 * Validates data for updating a product
 */
class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'branch_id' => ['sometimes', 'nullable', 'exists:branches,id'],
            'category_id' => ['sometimes', 'nullable', 'exists:product_categories,id'],
            'sku' => ['sometimes', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($productId)],
            'type' => ['sometimes', Rule::in(ProductType::values())],
            'status' => ['sometimes', Rule::in(ProductStatus::values())],
            'buy_unit_id' => ['sometimes', 'nullable', 'exists:unit_of_measures,id'],
            'sell_unit_id' => ['sometimes', 'nullable', 'exists:unit_of_measures,id'],
            'cost_price' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'selling_price' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'min_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'max_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'track_inventory' => ['sometimes', 'boolean'],
            'current_stock' => ['sometimes', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'reorder_quantity' => ['sometimes', 'integer', 'min:0'],
            'min_stock_level' => ['sometimes', 'integer', 'min:0'],
            'max_stock_level' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'attributes' => ['sometimes', 'nullable', 'array'],
            'images' => ['sometimes', 'nullable', 'array'],
            'images.*' => ['string'],
            'manufacturer' => ['sometimes', 'nullable', 'string', 'max:255'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'model' => ['sometimes', 'nullable', 'string', 'max:255'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999.999'],
            'weight_unit' => ['sometimes', 'nullable', 'string', 'max:20'],
            'length' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'width' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'height' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'dimension_unit' => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_taxable' => ['sometimes', 'boolean'],
            'tax_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'allow_discount' => ['sometimes', 'boolean'],
            'max_discount_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'category_id' => 'category',
            'buy_unit_id' => 'buy unit',
            'sell_unit_id' => 'sell unit',
            'cost_price' => 'cost price',
            'selling_price' => 'selling price',
            'min_price' => 'minimum price',
            'max_price' => 'maximum price',
            'track_inventory' => 'track inventory',
            'current_stock' => 'current stock',
            'reorder_level' => 'reorder level',
            'reorder_quantity' => 'reorder quantity',
            'min_stock_level' => 'minimum stock level',
            'max_stock_level' => 'maximum stock level',
            'weight_unit' => 'weight unit',
            'dimension_unit' => 'dimension unit',
            'is_taxable' => 'taxable',
            'tax_rate' => 'tax rate',
            'allow_discount' => 'allow discount',
            'max_discount_percentage' => 'maximum discount percentage',
            'is_featured' => 'featured',
            'sort_order' => 'sort order',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU is already in use.',
            'barcode.unique' => 'This barcode is already in use.',
            'category_id.exists' => 'The selected category does not exist.',
            'buy_unit_id.exists' => 'The selected buy unit does not exist.',
            'sell_unit_id.exists' => 'The selected sell unit does not exist.',
        ];
    }
}
