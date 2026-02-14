<?php

namespace App\Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'branch_id' => ['required', 'string', 'exists:organizations,id'],
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'order_number' => ['required', 'string', 'max:100', 'unique:orders,order_number'],
            'order_date' => ['required', 'date'],
            'status' => ['nullable', 'string', 'in:pending,confirmed,processing,completed,cancelled'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant ID is required',
            'tenant_id.exists' => 'The selected tenant does not exist',
            'branch_id.required' => 'Branch ID is required',
            'branch_id.exists' => 'The selected branch does not exist',
            'customer_id.required' => 'Customer ID is required',
            'customer_id.exists' => 'The selected customer does not exist',
            'order_number.required' => 'Order number is required',
            'order_number.unique' => 'This order number is already taken',
            'order_date.required' => 'Order date is required',
            'order_date.date' => 'Order date must be a valid date',
            'status.in' => 'Status must be one of: pending, confirmed, processing, completed, cancelled',
            'total_amount.numeric' => 'Total amount must be a number',
            'total_amount.min' => 'Total amount cannot be negative',
            'tax_amount.numeric' => 'Tax amount must be a number',
            'tax_amount.min' => 'Tax amount cannot be negative',
            'discount_amount.numeric' => 'Discount amount must be a number',
            'discount_amount.min' => 'Discount amount cannot be negative',
            'grand_total.numeric' => 'Grand total must be a number',
            'grand_total.min' => 'Grand total cannot be negative',
        ];
    }
}
