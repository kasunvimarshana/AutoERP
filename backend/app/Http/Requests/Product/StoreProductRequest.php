<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Uses ProductPolicy to enforce fine-grained authorization.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Modules\Product\Models\Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:inventory,service,combo,bundle,digital'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            
            // Status Flags
            'is_active' => ['nullable', 'boolean'],
            'is_purchasable' => ['nullable', 'boolean'],
            'is_sellable' => ['nullable', 'boolean'],
            
            // Pricing
            'buying_price' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:99999999999.99'],
            'mrp' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            
            // Units
            'buying_unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'selling_unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'stock_unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'unit_conversion_factor' => ['nullable', 'numeric', 'min:0.0001', 'max:9999.9999'],
            
            // Discount
            'buying_discount_type' => ['nullable', 'in:none,flat,percentage'],
            'buying_discount_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'selling_discount_type' => ['nullable', 'in:none,flat,percentage'],
            'selling_discount_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            
            // Profit Margin
            'profit_margin_type' => ['nullable', 'in:flat,percentage'],
            'profit_margin_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            
            // Taxation
            'tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'is_tax_inclusive' => ['nullable', 'boolean'],
            
            // Inventory Management
            'track_inventory' => ['nullable', 'boolean'],
            'track_serial' => ['nullable', 'boolean'],
            'track_batch' => ['nullable', 'boolean'],
            'has_expiry' => ['nullable', 'boolean'],
            'expiry_alert_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'valuation_method' => ['nullable', 'in:fifo,fefo,lifo,average'],
            
            // Stock Levels
            'min_stock_level' => ['nullable', 'numeric', 'min:0', 'max:999999999.9999'],
            'max_stock_level' => ['nullable', 'numeric', 'min:0', 'max:999999999.9999'],
            'reorder_level' => ['nullable', 'numeric', 'min:0', 'max:999999999.9999'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.9999'],
            
            // Physical Attributes
            'weight' => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'weight_unit' => ['nullable', 'string', 'max:10'],
            'length' => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'width' => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'dimension_unit' => ['nullable', 'string', 'max:10'],
            
            // Additional Information
            'barcode' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:255'],
            'warranty_period' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
            'attributes' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
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
            'name.required' => 'Product name is required',
            'name.max' => 'Product name cannot exceed 255 characters',
            'sku.required' => 'SKU is required',
            'sku.unique' => 'This SKU is already in use',
            'type.required' => 'Product type is required',
            'type.in' => 'Invalid product type. Must be one of: inventory, service, combo, bundle, or digital',
            'selling_price.required' => 'Selling price is required',
            'selling_price.min' => 'Selling price cannot be negative',
            'buying_price.min' => 'Buying price cannot be negative',
            'category_id.exists' => 'Selected category does not exist',
            'buying_unit_id.exists' => 'Selected buying unit does not exist',
            'selling_unit_id.exists' => 'Selected selling unit does not exist',
            'stock_unit_id.exists' => 'Selected stock unit does not exist',
            'tax_rate_id.exists' => 'Selected tax rate does not exist',
            'buying_discount_type.in' => 'Invalid buying discount type',
            'selling_discount_type.in' => 'Invalid selling discount type',
            'profit_margin_type.in' => 'Invalid profit margin type',
            'valuation_method.in' => 'Invalid valuation method',
            'expiry_alert_days.max' => 'Expiry alert days cannot exceed 365',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values if not provided
        $defaults = [
            'is_active' => true,
            'is_purchasable' => true,
            'is_sellable' => true,
            'track_inventory' => true,
            'track_serial' => false,
            'track_batch' => false,
            'has_expiry' => false,
            'is_tax_inclusive' => false,
            'buying_discount_type' => 'none',
            'selling_discount_type' => 'none',
            'profit_margin_type' => 'percentage',
            'valuation_method' => 'fifo',
            'unit_conversion_factor' => 1.0,
        ];

        foreach ($defaults as $key => $value) {
            if (!$this->has($key)) {
                $this->merge([$key => $value]);
            }
        }
    }
}
