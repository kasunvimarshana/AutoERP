<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'row_version' => ['required', 'integer', 'min:1'],
            'tenant_id' => ['sometimes', 'nullable', 'integer', 'exists:tenants,id'],
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'description' => ['required', 'string', 'max:1000'],
            'amount' => ['required', 'numeric'],
            'type' => ['required', 'in:debit,credit'],
            'status' => ['sometimes', 'in:imported,categorized,reconciled,excluded'],
            'matched_journal_entry_id' => ['sometimes', 'nullable', 'integer', 'exists:journal_entries,id'],
            'category_rule_id' => ['sometimes', 'nullable', 'integer', 'exists:bank_category_rules,id'],
        ];
    }
}
