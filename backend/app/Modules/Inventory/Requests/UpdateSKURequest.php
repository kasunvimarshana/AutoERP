<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update SKU Request
 * 
 * Validation rules for updating a SKU
 * 
 * @package App\Modules\Inventory\Requests
 */
class UpdateSKURequest extends FormRequest
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
        $skuId = $this->route('id');

        return [
            'product_id' => 'sometimes|required|integer|exists:products,id',
            'sku_code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('skus', 'sku_code')->ignore($skuId),
            ],
            'name' => 'sometimes|required|string|max:255',
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('skus', 'barcode')->ignore($skuId),
            ],
            'cost_price' => 'sometimes|required|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'reorder_level' => 'sometimes|required|integer|min:0',
            'status' => 'sometimes|required|string|in:active,inactive,discontinued',
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
            'product_id.exists' => 'The selected product does not exist.',
            'sku_code.unique' => 'This SKU code already exists.',
            'barcode.unique' => 'This barcode already exists.',
            'status.in' => 'The status must be one of: active, inactive, discontinued.',
        ];
    }
}
