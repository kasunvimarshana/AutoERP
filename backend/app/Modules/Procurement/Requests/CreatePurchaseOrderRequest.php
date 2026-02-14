<?php

namespace App\Modules\Procurement\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Purchase Order Request
 * 
 * Validation rules for creating a new purchase order
 * 
 * @package App\Modules\Procurement\Requests
 */
class CreatePurchaseOrderRequest extends FormRequest
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
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'po_number' => 'required|string|max:100|unique:purchase_orders,po_number',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'status' => 'required|string|in:draft,submitted,approved,received,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
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
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'po_number.unique' => 'This purchase order number already exists.',
            'status.in' => 'The status must be one of: draft, submitted, approved, received, cancelled.',
            'expected_delivery_date.after_or_equal' => 'Expected delivery date must be on or after the order date.',
            'approved_by.exists' => 'The selected approver does not exist.',
        ];
    }
}
