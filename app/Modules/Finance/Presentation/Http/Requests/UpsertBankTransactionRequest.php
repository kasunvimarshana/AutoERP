<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertBankTransactionRequest extends FormRequest
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
            'bank_account_id' => ['required', 'integer', 'min:1', 'exists:bank_accounts,id'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'value_date' => ['nullable', 'date'],
            'description' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'type' => ['nullable', 'string', 'max:255'],
            'balance' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'max:255'],
            'matched_journal_entry_id' => ['nullable', 'integer', 'min:1', 'exists:journal_entries,id'],
            'category_rule_id' => ['nullable', 'integer', 'min:1', 'exists:bank_category_rules,id'],
            'source_module' => ['nullable', 'string', 'max:100'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'created_by' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
