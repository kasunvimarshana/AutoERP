<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'check_out' => ['nullable', 'date_format:Y-m-d H:i:s', 'after:check_in'],
            'work_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'status' => ['required', 'in:present,absent,late,half-day,on-leave'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
