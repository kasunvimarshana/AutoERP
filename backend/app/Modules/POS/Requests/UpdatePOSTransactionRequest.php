<?php

namespace App\Modules\POS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePOSTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $transactionId = $this->route('id');

        return [
            'tenant_id' => 'sometimes|required|integer|exists:tenants,id',
            'transaction_number' => 'sometimes|required|string|max:255|unique:pos_transactions,transaction_number,' . $transactionId,
            'customer_id' => 'sometimes|nullable|integer|exists:customers,id',
            'cashier_id' => 'sometimes|required|integer|exists:users,id',
            'transaction_date' => 'sometimes|required|date',
            'payment_method' => 'sometimes|required|string|in:cash,card,digital',
            'subtotal' => 'sometimes|required|numeric|min:0',
            'tax_amount' => 'sometimes|nullable|numeric|min:0',
            'discount_amount' => 'sometimes|nullable|numeric|min:0',
            'total_amount' => 'sometimes|required|numeric|min:0',
            'amount_paid' => 'sometimes|required|numeric|min:0',
            'change_amount' => 'sometimes|nullable|numeric|min:0',
            'status' => 'sometimes|required|string|in:pending,completed,cancelled,refunded',
            'notes' => 'sometimes|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'cashier_id.exists' => 'The selected cashier does not exist.',
            'transaction_number.unique' => 'This transaction number is already in use.',
            'payment_method.in' => 'The payment method must be one of: cash, card, digital.',
            'status.in' => 'The status must be one of: pending, completed, cancelled, refunded.',
        ];
    }
}
