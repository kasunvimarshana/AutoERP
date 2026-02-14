<?php

namespace App\Modules\POS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePOSTransactionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'sometimes|required|integer|exists:tenants,id',
            'pos_transaction_id' => 'sometimes|required|integer|exists:pos_transactions,id',
            'sku_id' => 'sometimes|required|integer|exists:skus,id',
            'quantity' => 'sometimes|required|numeric|min:0.01',
            'unit_price' => 'sometimes|required|numeric|min:0',
            'discount' => 'sometimes|nullable|numeric|min:0',
            'tax_rate' => 'sometimes|nullable|numeric|min:0',
            'line_total' => 'sometimes|required|numeric|min:0',
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
