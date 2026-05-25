<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListInvoiceLineRequest extends FormRequest
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
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('invoice.pagination.max_per_page', 200)],
            'invoice_id' => ['nullable', 'integer', 'min:1', 'exists:invoices,id'],
            'invoice_reference_id' => ['nullable', 'integer', 'min:1', 'exists:invoice_references,id'],
            'item_type' => ['nullable', 'string', 'max:255'],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id']
        ];
    }
}