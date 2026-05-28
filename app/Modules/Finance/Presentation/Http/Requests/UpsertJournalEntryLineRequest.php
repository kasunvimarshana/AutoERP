<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertJournalEntryLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
            'journal_entry_id' => ['required', 'integer', 'min:1', 'exists:journal_entries,id', ],
            'account_id' => ['required', 'integer', 'min:1', 'exists:accounts,id', ],
            'description' => ['nullable', 'string', ],
            'debit_amount' => ['nullable', 'numeric', 'gt:0', 'required_without:credit_amount'],
            'credit_amount' => ['nullable', 'numeric', 'gt:0', 'required_without:debit_amount'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id', ],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'base_debit_amount' => ['nullable', 'numeric', 'gte:0'],
            'base_credit_amount' => ['nullable', 'numeric', 'gte:0'],
            'cost_center_id' => ['nullable', 'integer', 'min:1', 'exists:cost_centers,id', ],
            'tax_rate_id' => ['nullable', 'integer', 'min:1', 'exists:tax_rates,id', ],
            'tax_amount' => ['nullable', 'numeric', 'gte:0'],
            'line_number' => ['nullable', 'integer', 'min:1', ],
        ];
    }
}
