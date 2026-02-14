<?php

namespace App\Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:100', 'unique:product_variants,sku'],
            'variant_name' => ['required', 'string', 'max:255'],
            'attributes' => ['nullable', 'array'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:product_variants,barcode'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'Variant SKU is required',
            'sku.unique' => 'This variant SKU is already in use',
            'variant_name.required' => 'Variant name is required',
            'cost_price.required' => 'Cost price is required',
            'cost_price.min' => 'Cost price must be at least 0',
            'selling_price.required' => 'Selling price is required',
            'selling_price.min' => 'Selling price must be at least 0',
            'barcode.unique' => 'This barcode is already in use',
            'weight.min' => 'Weight must be at least 0',
            'status.in' => 'Status must be either active or inactive',
        ];
    }
}
