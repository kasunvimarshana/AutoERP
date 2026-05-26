<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertArTransactionRequest extends FormRequest
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
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'row_version' => ['nullable', 'integer', 'min:1'],
            'party_type' => ['nullable', 'string', 'max:255', ],
            'party_id' => ['nullable', 'integer', 'min:1', ],
            'account_id' => ['required', 'integer', 'min:1', 'exists:accounts,id', ],
            'transaction_type' => ['required', 'string', 'max:255', ],
            'reference_type' => ['nullable', 'string', 'max:255', ],
            'reference_id' => ['nullable', 'integer', 'min:1', ],
            'debit_amount' => ['nullable', 'numeric', ],
            'credit_amount' => ['nullable', 'numeric', ],
            'balance_after' => ['nullable', 'numeric', ],
            'transaction_date' => ['required', 'date', ],
            'due_date' => ['nullable', 'date', ],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id', ],
            'exchange_rate' => ['nullable', 'numeric', ],
            'is_reconciled' => ['nullable', 'boolean', ],
        ];
    }
}
