<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPurchaseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant_id' => $this->input('tenant_id', $this->attributes->get('current_tenant_id')),
            'organization_unit_id' => $this->input(
                'organization_unit_id',
                $this->attributes->get('current_organization_unit_id'),
            ),
            'created_by' => $this->input('created_by', $this->attributes->get('current_user_id')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'invoice_type' => ['nullable', 'in:standard,credit_note,debit_note,purchase'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'party_id' => ['required', 'integer', 'min:1', 'exists:suppliers,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric'],
            'header_discount_type' => ['nullable', 'in:percentage,fixed'],
            'header_discount_value' => ['nullable', 'numeric', 'min:0'],
            'header_tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'ap_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'notes' => ['nullable', 'string'],
            'source_type' => ['nullable', 'in:PO,GRN,po,grn'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'references' => ['nullable', 'array'],
            'references.*.document_type' => ['required_with:references', 'string', 'max:255'],
            'references.*.document_id' => ['required_with:references', 'integer', 'min:1'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.reference' => ['nullable', 'string', 'max:255'],
            'lines.*.item_type' => ['nullable', 'string', 'max:255'],
            'lines.*.item_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', 'in:percentage,fixed'],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'lines.*.account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
        ];
    }
}
