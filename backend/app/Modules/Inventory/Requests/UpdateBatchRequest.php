<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Batch Request
 * 
 * Validation rules for updating a batch
 * 
 * @package App\Modules\Inventory\Requests
 */
class UpdateBatchRequest extends FormRequest
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
        $batchId = $this->route('id');

        return [
            'sku_id' => 'sometimes|required|integer|exists:skus,id',
            'batch_number' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('batches', 'batch_number')->ignore($batchId),
            ],
            'manufacturing_date' => 'sometimes|required|date',
            'expiry_date' => 'sometimes|required|date|after:manufacturing_date',
            'quantity' => 'sometimes|required|integer|min:0',
            'status' => 'sometimes|required|string|in:active,expired,recalled,depleted',
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
            'sku_id.exists' => 'The selected SKU does not exist.',
            'batch_number.unique' => 'This batch number already exists.',
            'expiry_date.after' => 'The expiry date must be after the manufacturing date.',
            'status.in' => 'The status must be one of: active, expired, recalled, depleted.',
        ];
    }
}
