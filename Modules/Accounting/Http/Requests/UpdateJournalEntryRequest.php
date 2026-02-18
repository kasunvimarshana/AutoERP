<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entryId = $this->route('id');

        return [
            'entry_date' => ['sometimes', 'required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'tags' => ['nullable', 'array'],
            'lines' => ['sometimes', 'required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.debit_amount' => ['required', 'numeric', 'min:0'],
            'lines.*.credit_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
