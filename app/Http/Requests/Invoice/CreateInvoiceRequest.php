<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('invoices.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
            'order_id' => ['sometimes', 'nullable', 'uuid', 'exists:orders,id'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'billing_address' => ['sometimes', 'array'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'issue_date' => ['sometimes', 'date'],
            // due_date must be >= issue_date when both are provided; if issue_date is absent, the after_or_equal rule uses today as the implicit baseline
            'due_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:issue_date'],
            'items' => ['sometimes', 'array'],
            'items.*.product_id' => ['sometimes', 'nullable', 'uuid', 'exists:products,id'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.000001'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
