<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertBankAccountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', ],
            'bank_name' => ['required', 'string', 'max:255', ],
            'account_number' => ['required', 'string', 'max:255', ],
            'routing_number' => ['nullable', 'string', 'max:255', ],
            'iban' => ['nullable', 'string', 'max:255', ],
            'swift_bic' => ['nullable', 'string', 'max:255', ],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id', ],
            'account_id' => ['required', 'integer', 'min:1', 'exists:accounts,id', ],
            'opening_balance' => ['nullable', 'numeric', ],
            'current_balance' => ['nullable', 'numeric', ],
            'last_reconciled_at' => ['nullable', 'date', ],
            'last_reconciled_balance' => ['nullable', 'numeric', ],
            'feed_provider' => ['nullable', 'string', 'max:255', ],
            'feed_credentials_enc' => ['nullable', 'string', ],
            'is_active' => ['nullable', 'boolean', ],
            'created_by' => ['nullable', 'integer', 'min:1', ],
        ];
    }
}
