<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListBankTransactionRequest extends FormRequest
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
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('finance.pagination.max_per_page', 200)],
            'bank_account_id' => ['nullable', 'integer', 'min:1', 'exists:bank_accounts,id'],
            'status' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'matched_journal_entry_id' => ['nullable', 'integer', 'min:1', 'exists:journal_entries,id'],
            'category_rule_id' => ['nullable', 'integer', 'min:1', 'exists:bank_category_rules,id'],
        ];
    }
}
