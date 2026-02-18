<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'journal_entry_id' => ['required', 'integer', 'exists:journal_entries,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string'],
            'debit_amount' => ['required', 'numeric', 'min:0'],
            'credit_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
        ];
    }
}
