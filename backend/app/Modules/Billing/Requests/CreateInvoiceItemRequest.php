<?php

namespace App\Modules\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceItemRequest extends FormRequest
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
            'sku_id' => 'nullable|integer|exists:skus,id',
            'description' => 'required|string',
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
            'invoice_id.exists' => 'The selected invoice does not exist.',
            'sku_id.exists' => 'The selected SKU does not exist.',
        ];
    }
}
