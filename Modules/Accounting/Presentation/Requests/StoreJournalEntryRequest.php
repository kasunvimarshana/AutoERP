<?php

namespace Modules\Accounting\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference'          => ['nullable', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'entry_date'         => ['required', 'date'],
            'lines'              => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'uuid'],
            'lines.*.debit'      => ['required', 'numeric', 'min:0'],
            'lines.*.credit'     => ['required', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string'],
        ];
    }
}
