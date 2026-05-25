<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertInvoiceLineRequest extends FormRequest
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
            'invoice_id' => array_merge($required, ['integer', 'min:1', 'exists:invoices,id']),
            'invoice_reference_id' => ['nullable', 'integer', 'min:1', 'exists:invoice_references,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'item_type' => ['nullable', 'string', 'max:255'],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
            'uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'quantity' => array_merge($required, ['numeric']),
            'unit_price' => array_merge($required, ['numeric']),
            'discount_type' => ['nullable', 'string', 'max:255'],
            'discount_value' => ['nullable', 'numeric'],
            'discount_amount' => ['nullable', 'numeric'],
            'gross_amount' => ['nullable', 'numeric'],
            'line_total' => ['nullable', 'numeric'],
            'tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'tax_amount' => ['nullable', 'numeric'],
            'line_total_with_tax' => ['nullable', 'numeric'],
            'account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id']
        ];
    }
}