<?php

namespace Modules\HR\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'   => ['required', 'uuid'],
            'employee_id' => ['required', 'uuid'],
            'work_date'   => ['nullable', 'date_format:Y-m-d'],
            'check_in'    => ['nullable', 'date_format:Y-m-d H:i:s'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ];
    }
}
