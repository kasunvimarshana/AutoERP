<?php

namespace Modules\Currency\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_currency_code' => ['required', 'string', 'size:3', 'alpha'],
            'to_currency_code'   => ['required', 'string', 'size:3', 'alpha'],
            'rate'               => ['required', 'numeric', 'gt:0'],
            'source'             => ['nullable', 'string', 'in:manual,automatic'],
            'effective_date'     => ['nullable', 'date'],
        ];
    }
}
