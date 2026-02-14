<?php

namespace App\Modules\POS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePOSTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'transaction_number' => 'required|string|max:255|unique:pos_transactions,transaction_number',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'cashier_id' => 'required|integer|exists:users,id',
            'transaction_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,card,digital',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'change_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:pending,completed,cancelled,refunded',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.sku_id' => 'required|integer|exists:skus,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'items.*.line_total' => 'required|numeric|min:0',
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
            'items.*.sku_id.exists' => 'One or more selected SKUs do not exist.',
        ];
    }
}
