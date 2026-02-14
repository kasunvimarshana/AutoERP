<?php

namespace App\Modules\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoiceId = $this->route('id');
        
        return [
            'tenant_id' => 'sometimes|integer|exists:tenants,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'invoice_number' => 'sometimes|string|max:255|unique:invoices,invoice_number,' . $invoiceId,
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:invoice_date',
            'status' => 'sometimes|string|in:draft,sent,paid,overdue,cancelled',
            'subtotal' => 'sometimes|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'sometimes|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'balance' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.sku_id' => 'nullable|integer|exists:skus,id',
            'items.*.description' => 'required|string',
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
            'invoice_number.unique' => 'This invoice number is already in use.',
            'due_date.after_or_equal' => 'The due date must be on or after the invoice date.',
            'status.in' => 'The status must be one of: draft, sent, paid, overdue, cancelled.',
            'items.*.sku_id.exists' => 'One or more selected SKUs do not exist.',
        ];
    }
}
