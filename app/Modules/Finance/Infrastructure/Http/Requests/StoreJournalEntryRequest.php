<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
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
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'fiscal_period_id' => ['required', 'integer', 'exists:fiscal_periods,id'],
            'entry_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'entry_type' => ['sometimes', 'in:manual,auto,system'],
            'reference_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'reference_id' => ['sometimes', 'nullable', 'integer'],
            'description' => ['sometimes', 'nullable', 'string'],
            'entry_date' => ['required', 'date'],
            'posting_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'in:draft,posted,reversed'],
            'is_reversed' => ['sometimes', 'boolean'],
            'reversal_entry_id' => ['sometimes', 'nullable', 'integer', 'exists:journal_entries,id'],
            'created_by' => ['required', 'integer', 'exists:users,id'],
            'posted_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'posted_at' => ['sometimes', 'nullable', 'date'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['sometimes', 'nullable', 'string'],
            'lines.*.debit_amount' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.credit_amount' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.currency_id' => ['sometimes', 'nullable', 'integer', 'exists:currencies,id'],
            'lines.*.exchange_rate' => ['sometimes', 'numeric', 'min:0.000001'],
            'lines.*.base_debit_amount' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.base_credit_amount' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.cost_center_id' => ['sometimes', 'nullable', 'integer', 'exists:org_units,id'],
            'lines.*.metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
