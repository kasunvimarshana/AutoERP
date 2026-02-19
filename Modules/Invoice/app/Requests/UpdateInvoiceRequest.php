<?php

declare(strict_types=1);

namespace Modules\Invoice\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Invoice\Enums\InvoiceStatus;

/**
 * Update Invoice Request
 *
 * Validates data for updating an invoice
 */
class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $invoiceId = $this->route('id');

        return [
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'invoice_number' => ['sometimes', 'string', 'max:255', 'unique:invoices,invoice_number,'.$invoiceId],
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', InvoiceStatus::values())],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'vehicle_id' => 'vehicle',
            'branch_id' => 'branch',
            'invoice_number' => 'invoice number',
            'invoice_date' => 'invoice date',
            'due_date' => 'due date',
            'tax_rate' => 'tax rate',
            'discount_amount' => 'discount amount',
            'payment_terms' => 'payment terms',
        ];
    }
}
