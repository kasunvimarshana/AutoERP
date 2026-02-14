<?php

namespace App\Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id');
        
        return [
            'tenant_id' => ['sometimes', 'string', 'exists:tenants,id'],
            'sku' => ['sometimes', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit_of_measure' => ['sometimes', 'string', 'max:50'],
            'is_variant_product' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'type' => ['sometimes', 'string', 'in:product,service'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist',
            'sku.unique' => 'This SKU is already in use',
            'type.in' => 'Product type must be either product or service',
            'status.in' => 'Status must be either active or inactive',
            'tax_rate.min' => 'Tax rate must be at least 0',
            'tax_rate.max' => 'Tax rate cannot exceed 1 (100%)',
        ];
    }
}
