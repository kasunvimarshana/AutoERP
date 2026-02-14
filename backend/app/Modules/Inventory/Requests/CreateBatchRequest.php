<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Batch Request
 * 
 * Validation rules for creating a new batch
 * 
 * @package App\Modules\Inventory\Requests
 */
class CreateBatchRequest extends FormRequest
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
            'sku_id' => 'required|integer|exists:skus,id',
            'batch_number' => 'required|string|max:100|unique:batches,batch_number',
            'manufacturing_date' => 'required|date',
            'expiry_date' => 'required|date|after:manufacturing_date',
            'quantity' => 'required|integer|min:0',
            'status' => 'required|string|in:active,expired,recalled,depleted',
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
            'sku_id.exists' => 'The selected SKU does not exist.',
            'batch_number.unique' => 'This batch number already exists.',
            'expiry_date.after' => 'The expiry date must be after the manufacturing date.',
            'status.in' => 'The status must be one of: active, expired, recalled, depleted.',
        ];
    }
}
