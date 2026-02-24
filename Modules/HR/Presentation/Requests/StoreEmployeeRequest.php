<?php

namespace Modules\HR\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255'],
            'position'      => ['required', 'string', 'max:255'],
            'salary'        => ['nullable', 'numeric', 'min:0'],
            'hire_date'     => ['required', 'date'],
            'department_id' => ['nullable', 'uuid'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'status'        => ['nullable', 'string', 'in:active,inactive,terminated'],
        ];
    }
}
