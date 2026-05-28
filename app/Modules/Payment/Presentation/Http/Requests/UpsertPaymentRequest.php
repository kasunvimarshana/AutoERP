<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPaymentRequest extends FormRequest
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
            'party_type' => ['nullable', 'string', 'max:255'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:255'],
            'payment_number' => array_merge($required, ['string', 'max:255']),
            'payment_date' => array_merge($required, ['date']),
            'amount' => array_merge($required, ['numeric', 'gt:0']),
            'allocated_amount' => ['nullable', 'numeric', 'gte:0'],
            'direction' => ['nullable', 'in:inbound,outbound'],
            'payment_group_id' => ['nullable', 'integer', 'min:1', 'exists:payment_groups,id'],
            'payment_method_id' => array_merge($required, ['integer', 'min:1', 'exists:payment_methods,id']),
            'account_id' => array_merge($required, ['integer', 'min:1', 'exists:accounts,id']),
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'base_amount' => ['nullable', 'numeric', 'gt:0'],
            'status' => ['nullable', 'in:draft,posted,reconciled,voided,reversed,failed,partially_allocated,fully_allocated,pending'],
            'notes' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'journal_entry_id' => ['nullable', 'integer', 'min:1', 'exists:journal_entries,id'],
            'reversal_of_payment_id' => ['nullable', 'integer', 'min:1', 'exists:payments,id'],
            'created_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'posted_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'voided_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'reversed_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'posted_at' => ['nullable', 'date'],
            'voided_at' => ['nullable', 'date'],
            'reversed_at' => ['nullable', 'date'],
        ];
    }
}
