<?php

namespace Modules\Currency\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'           => ['required', 'string', 'size:3', 'alpha'],
            'name'           => ['required', 'string', 'max:100'],
            'symbol'         => ['nullable', 'string', 'max:10'],
            'decimal_places' => ['nullable', 'integer', 'min:0', 'max:8'],
        ];
    }
}
