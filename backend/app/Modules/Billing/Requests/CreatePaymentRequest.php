<?php

namespace App\Modules\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'invoice_id' => 'required|integer|exists:invoices,id',
            'payment_number' => 'required|string|max:255|unique:payments,payment_number',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,card,bank_transfer,digital',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:pending,completed,failed,refunded',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'invoice_id.exists' => 'The selected invoice does not exist.',
            'payment_number.unique' => 'This payment number is already in use.',
            'payment_method.in' => 'The payment method must be one of: cash, card, bank_transfer, digital.',
            'status.in' => 'The status must be one of: pending, completed, failed, refunded.',
        ];
    }
}
