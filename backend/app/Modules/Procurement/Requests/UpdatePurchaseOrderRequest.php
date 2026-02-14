<?php

namespace App\Modules\Procurement\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Purchase Order Request
 * 
 * Validation rules for updating a purchase order
 * 
 * @package App\Modules\Procurement\Requests
 */
class UpdatePurchaseOrderRequest extends FormRequest
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
            'supplier_id' => 'sometimes|required|integer|exists:suppliers,id',
            'po_number' => 'sometimes|required|string|max:100',
            'order_date' => 'sometimes|required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'status' => 'sometimes|required|string|in:draft,submitted,approved,received,cancelled',
            'subtotal' => 'sometimes|required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'sometimes|required|numeric|min:0',
            'notes' => 'nullable|string',
            'approved_by' => 'nullable|integer|exists:users,id',
            'approved_at' => 'nullable|date',
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
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'status.in' => 'The status must be one of: draft, submitted, approved, received, cancelled.',
            'expected_delivery_date.after_or_equal' => 'Expected delivery date must be on or after the order date.',
            'approved_by.exists' => 'The selected approver does not exist.',
        ];
    }
}
