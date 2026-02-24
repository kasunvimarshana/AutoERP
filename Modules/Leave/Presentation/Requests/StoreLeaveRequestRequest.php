<?php

namespace Modules\Leave\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'    => ['required', 'string'],
            'leave_type_id'  => ['required', 'string'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date', 'after_or_equal:start_date'],
            'days_requested' => ['required', 'integer', 'min:1'],
            'reason'         => ['nullable', 'string'],
        ];
    }
}
