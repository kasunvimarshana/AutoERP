<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PurchaseIntegrationActionRequest extends FormRequest
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
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
            'document_type_id' => ['nullable', 'integer', 'min:1', 'exists:document_types,id'],
            'document_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'max:120'],
            'action_name' => ['nullable', 'string', 'max:120'],
            'link_id' => ['nullable', 'integer', 'min:1'],
            'source_line_id' => ['nullable', 'integer', 'min:1'],
            'document_line_id' => ['nullable', 'integer', 'min:1'],
            'linked_quantity' => ['nullable', 'numeric', 'gt:0'],
            'linked_amount' => ['nullable', 'numeric', 'gte:0'],
            'payment_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:payments,id',
                'prohibited_with:advance_payment_id',
            ],
            'advance_payment_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:advance_payments,id',
                'prohibited_with:payment_id',
            ],
            'allocated_amount' => ['nullable', 'numeric', 'gt:0'],
            'base_allocated_amount' => ['nullable', 'numeric', 'gt:0'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'payment_number' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'payment_method_id' => ['nullable', 'integer', 'min:1', 'exists:payment_methods,id'],
            'account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'remaining_amount' => ['nullable', 'numeric', 'gte:0'],
            'base_amount' => ['nullable', 'numeric', 'gt:0'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'journal_entry_id' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
            'advance_number' => ['nullable', 'string', 'max:255'],
            'advance_date' => ['nullable', 'date'],
            'allocate_now' => ['nullable', 'boolean'],
        ];
    }
}
