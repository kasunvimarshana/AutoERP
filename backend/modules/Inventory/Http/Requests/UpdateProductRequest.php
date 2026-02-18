<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Product Request
 *
 * Validates data for updating an existing product.
 */
class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization should be handled by policies/middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'product_type' => 'sometimes|string|in:inventory,service,bundle,composite',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'base_uom_id' => 'nullable|uuid',
            'track_inventory' => 'boolean',
            'track_batches' => 'boolean',
            'track_serials' => 'boolean',
            'has_expiry' => 'boolean',
            'reorder_level' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'cost_method' => 'sometimes|string|in:fifo,lifo,average,standard',
            'standard_cost' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'status' => 'sometimes|string|in:draft,active,inactive,discontinued',
            'custom_attributes' => 'nullable|array',
            'barcode' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'weight_uom' => 'nullable|string|max:20',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'dimension_uom' => 'nullable|string|max:20',
            'image_url' => 'nullable|url',

            // Variants
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|uuid',
            'variants.*.name' => 'required_with:variants|string|max:255',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.barcode' => 'nullable|string|max:100',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.cost' => 'nullable|numeric|min:0',

            // Custom Attributes
            'attributes' => 'nullable|array',
            'attributes.*.id' => 'nullable|uuid',
            'attributes.*.attribute_name' => 'required_with:attributes|string|max:100',
            'attributes.*.attribute_value' => 'required_with:attributes|string',
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
            'name.required' => 'Product name is required.',
            'product_type.in' => 'Product type must be one of: inventory, service, bundle, composite.',
            'cost_method.in' => 'Cost method must be one of: fifo, lifo, average, standard.',
            'status.in' => 'Status must be one of: draft, active, inactive, discontinued.',
        ];
    }
}
