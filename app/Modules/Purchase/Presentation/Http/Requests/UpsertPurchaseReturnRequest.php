<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'reference' => ['nullable', 'string', 'max:255'],
            'supplier_id' => array_merge($required, ['integer', 'min:1', 'exists:suppliers,id']),
            'original_purchase_order_id' => ['nullable', 'integer', 'min:1', 'exists:purchase_orders,id'],
            'original_grn_id' => ['nullable', 'integer', 'min:1', 'exists:grn_headers,id'],
            'original_invoice_id' => ['nullable', 'integer', 'min:1', 'exists:invoices,id'],
            'return_number' => array_merge($required, ['string', 'max:255']),
            'status' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric'],
            'return_date' => array_merge($required, ['date']),
            'return_reason' => ['nullable', 'string', 'max:255'],
            'subtotal' => ['nullable', 'numeric'],
            'line_tax_total' => ['nullable', 'numeric'],
            'line_discount_total' => ['nullable', 'numeric'],
            'line_restocking_total' => ['nullable', 'numeric'],
            'header_discount_type' => ['nullable', 'string', 'max:255'],
            'header_discount_value' => ['nullable', 'numeric'],
            'header_discount_amount' => ['nullable', 'numeric'],
            'header_tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'header_tax_amount' => ['nullable', 'numeric'],
            'discount_total' => ['nullable', 'numeric'],
            'tax_total' => ['nullable', 'numeric'],
            'debit_note_total' => ['nullable', 'numeric'],
            'credit_note_total' => ['nullable', 'numeric'],
            'grand_total' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', 'min:1']
        ];
    }
}