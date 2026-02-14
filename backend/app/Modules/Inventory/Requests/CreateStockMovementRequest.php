<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create StockMovement Request
 * 
 * Validation rules for creating a new stock movement
 * 
 * @package App\Modules\Inventory\Requests
 */
class CreateStockMovementRequest extends FormRequest
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
            'batch_id' => 'nullable|integer|exists:batches,id',
            'type' => 'required|string|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'balance_after' => 'required|integer|min:0',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'created_by' => 'required|integer|exists:users,id',
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
            'batch_id.exists' => 'The selected batch does not exist.',
            'type.in' => 'The type must be one of: in, out, adjustment.',
            'created_by.exists' => 'The selected user does not exist.',
        ];
    }
}
