<?php

namespace Modules\HR\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'code'           => ['required', 'string', 'max:50'],
            'type'           => ['required', 'string', 'in:earning,deduction'],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'description'    => ['nullable', 'string', 'max:1000'],
        ];
    }
}
