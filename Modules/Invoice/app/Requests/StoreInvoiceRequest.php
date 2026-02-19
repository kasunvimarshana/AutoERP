<?php

declare(strict_types=1);

namespace Modules\Invoice\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Invoice\Enums\InvoiceStatus;

/**
 * Store Invoice Request
 *
 * Validates data for creating a new invoice
 */
class StoreInvoiceRequest extends FormRequest
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
        return [
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'invoice_number' => ['nullable', 'string', 'max:255', 'unique:invoices,invoice_number'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:'.implode(',', InvoiceStatus::values())],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.item_type' => ['required_with:items', 'string', 'in:labor,part,service'],
            'items.*.description' => ['required_with:items', 'string'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
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
            'job_card_id' => 'job card',
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
