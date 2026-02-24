<?php

namespace Modules\Accounting\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'bank_name'      => ['required', 'string', 'max:255'],
            'currency'       => ['nullable', 'string', 'size:3'],
            'notes'          => ['nullable', 'string'],
            'is_active'      => ['nullable', 'boolean'],
        ];
    }
}
