<?php

namespace Modules\Accounting\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReconcileBankTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'journal_entry_id' => ['required', 'uuid'],
        ];
    }
}
