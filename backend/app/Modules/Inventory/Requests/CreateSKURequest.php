<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create SKU Request
 * 
 * Validation rules for creating a new SKU
 * 
 * @package App\Modules\Inventory\Requests
 */
class CreateSKURequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'product_id' => 'required|integer|exists:products,id',
            'sku_code' => 'required|string|max:100|unique:skus,sku_code',
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:100|unique:skus,barcode',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'status' => 'required|string|in:active,inactive,discontinued',
        ];
    }

    /**
     * Get custom messages for validator errors
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'product_id.exists' => 'The selected product does not exist.',
            'sku_code.unique' => 'This SKU code already exists.',
            'barcode.unique' => 'This barcode already exists.',
            'status.in' => 'The status must be one of: active, inactive, discontinued.',
        ];
    }
}
