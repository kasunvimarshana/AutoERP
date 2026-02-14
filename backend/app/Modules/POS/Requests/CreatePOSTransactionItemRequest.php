<?php

namespace App\Modules\POS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePOSTransactionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'pos_transaction_id' => 'required|integer|exists:pos_transactions,id',
            'sku_id' => 'required|integer|exists:skus,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'line_total' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'pos_transaction_id.exists' => 'The selected POS transaction does not exist.',
            'sku_id.exists' => 'The selected SKU does not exist.',
            'quantity.min' => 'The quantity must be at least 0.01.',
        ];
    }
}
