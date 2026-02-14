<?php

namespace App\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'invoice_id' => ['nullable', 'string', 'exists:invoices,id'],
            'payment_number' => ['required', 'string', 'max:100', 'unique:payments,payment_number'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:cash,card,bank_transfer,cheque,online,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:3'],
            'status' => ['nullable', 'string', 'in:pending,completed,failed,refunded,cancelled'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
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
            'invoice_id.exists' => 'The selected invoice does not exist',
            'payment_number.required' => 'Payment number is required',
            'payment_number.unique' => 'This payment number is already taken',
            'payment_date.required' => 'Payment date is required',
            'payment_date.date' => 'Payment date must be a valid date',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Payment method must be one of: cash, card, bank_transfer, cheque, online, other',
            'amount.required' => 'Payment amount is required',
            'amount.numeric' => 'Payment amount must be a number',
            'amount.min' => 'Payment amount must be greater than 0',
            'status.in' => 'Status must be one of: pending, completed, failed, refunded, cancelled',
        ];
    }
}
