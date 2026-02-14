<?php

namespace App\Modules\Invoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
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
            'order_id' => ['nullable', 'string', 'exists:orders,id'],
            'invoice_number' => ['required', 'string', 'max:100', 'unique:invoices,invoice_number'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'status' => ['nullable', 'string', 'in:draft,sent,paid,overdue,cancelled'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'balance_due' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', 'string', 'in:unpaid,partial,paid'],
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
            'order_id.exists' => 'The selected order does not exist',
            'invoice_number.required' => 'Invoice number is required',
            'invoice_number.unique' => 'This invoice number is already taken',
            'invoice_date.required' => 'Invoice date is required',
            'invoice_date.date' => 'Invoice date must be a valid date',
            'due_date.required' => 'Due date is required',
            'due_date.date' => 'Due date must be a valid date',
            'due_date.after_or_equal' => 'Due date must be on or after invoice date',
            'status.in' => 'Status must be one of: draft, sent, paid, overdue, cancelled',
            'payment_status.in' => 'Payment status must be one of: unpaid, partial, paid',
            'subtotal.numeric' => 'Subtotal must be a number',
            'subtotal.min' => 'Subtotal cannot be negative',
            'tax_amount.numeric' => 'Tax amount must be a number',
            'tax_amount.min' => 'Tax amount cannot be negative',
            'discount_amount.numeric' => 'Discount amount must be a number',
            'discount_amount.min' => 'Discount amount cannot be negative',
            'total_amount.numeric' => 'Total amount must be a number',
            'total_amount.min' => 'Total amount cannot be negative',
            'paid_amount.numeric' => 'Paid amount must be a number',
            'paid_amount.min' => 'Paid amount cannot be negative',
            'balance_due.numeric' => 'Balance due must be a number',
            'balance_due.min' => 'Balance due cannot be negative',
        ];
    }
}
