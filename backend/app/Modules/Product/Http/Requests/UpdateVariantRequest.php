<?php

namespace App\Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantId = $this->route('variantId');
        
        return [
            'sku' => ['sometimes', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'variant_name' => ['sometimes', 'string', 'max:255'],
            'attributes' => ['nullable', 'array'],
            'cost_price' => ['sometimes', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'barcode')->ignore($variantId)],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'This variant SKU is already in use',
            'cost_price.min' => 'Cost price must be at least 0',
            'selling_price.min' => 'Selling price must be at least 0',
            'barcode.unique' => 'This barcode is already in use',
            'weight.min' => 'Weight must be at least 0',
            'status.in' => 'Status must be either active or inactive',
        ];
    }
}
