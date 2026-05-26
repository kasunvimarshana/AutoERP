<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
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
            'amount' => array_merge($required, ['numeric']),
            'direction' => ['nullable', 'string', 'max:255'],
            'payment_method_id' => array_merge($required, ['integer', 'min:1', 'exists:payment_methods,id']),
            'account_id' => array_merge($required, ['integer', 'min:1', 'exists:accounts,id']),
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric'],
            'base_amount' => array_merge($required, ['numeric']),
            'status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'journal_entry_id' => ['nullable', 'integer', 'min:1', 'exists:journal_entries,id']
        ];
    }
}