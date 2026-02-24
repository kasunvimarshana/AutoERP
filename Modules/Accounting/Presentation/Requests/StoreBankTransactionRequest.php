<?php

namespace Modules\Accounting\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_id'  => ['required', 'uuid'],
            'type'             => ['required', 'string', 'in:credit,debit'],
            'amount'           => ['required', 'numeric', 'min:0.00000001'],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'description'      => ['required', 'string', 'max:500'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}
