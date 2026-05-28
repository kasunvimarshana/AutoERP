<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PurchaseWorkflowActionRequest extends FormRequest
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
            'actor_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string'],
            'document_type_id' => ['nullable', 'integer', 'min:1', 'exists:document_types,id'],
            'document_id' => ['nullable', 'integer', 'min:1', 'exists:documents,id'],
            'document_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'payment_id' => ['nullable', 'integer', 'min:1', 'exists:payments,id'],
            'advance_payment_id' => ['nullable', 'integer', 'min:1', 'exists:advance_payments,id'],
            'allocated_amount' => ['nullable', 'numeric', 'gt:0'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'base_allocated_amount' => ['nullable', 'numeric', 'gt:0'],
            'entry_payload' => ['nullable', 'array'],
            'lines_payload' => ['nullable', 'array'],
            'journal_entry_id' => ['nullable', 'integer', 'min:1'],
            'finance_reversed' => ['nullable', 'boolean'],
            'inventory_reversed' => ['nullable', 'boolean'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
