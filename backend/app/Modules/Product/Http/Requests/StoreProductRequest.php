<?php

namespace App\Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit_of_measure' => ['required', 'string', 'max:50'],
            'is_variant_product' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'type' => ['required', 'string', 'in:product,service'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant ID is required',
            'tenant_id.exists' => 'The selected tenant does not exist',
            'sku.required' => 'SKU is required',
            'sku.unique' => 'This SKU is already in use',
            'name.required' => 'Product name is required',
            'unit_of_measure.required' => 'Unit of measure is required',
            'type.required' => 'Product type is required',
            'type.in' => 'Product type must be either product or service',
            'status.in' => 'Status must be either active or inactive',
            'tax_rate.min' => 'Tax rate must be at least 0',
            'tax_rate.max' => 'Tax rate cannot exceed 1 (100%)',
        ];
    }
}
