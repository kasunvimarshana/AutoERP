<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertBankReconciliationRequest extends FormRequest
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
            'bank_account_id' => ['required', 'integer', 'min:1', 'exists:bank_accounts,id', ],
            'period_start' => ['required', 'date', ],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', ],
            'opening_balance' => ['required', 'numeric', ],
            'closing_balance' => ['required', 'numeric', ],
            'statement_balance' => ['required', 'numeric', ],
            'difference' => ['required', 'numeric', ],
            'status' => ['nullable', 'string', 'max:255', ],
            'completed_by' => ['nullable', 'integer', 'min:1', 'exists:users,id', ],
            'completed_at' => ['nullable', 'date', ],
            'approved_by' => ['nullable', 'integer', 'min:1', 'exists:users,id', ],
            'approved_at' => ['nullable', 'date', ],
            'notes' => ['nullable', 'string', ],
            'created_by' => ['nullable', 'integer', 'min:1', ],
        ];
    }
}
