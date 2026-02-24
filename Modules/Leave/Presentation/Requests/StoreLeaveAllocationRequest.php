<?php

namespace Modules\Leave\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'   => ['required', 'uuid'],
            'leave_type_id' => ['required', 'uuid'],
            'total_days'    => ['required', 'numeric', 'min:0.5'],
            'period_label'  => ['nullable', 'string', 'max:50'],
            'valid_from'    => ['nullable', 'date'],
            'valid_to'      => ['nullable', 'date', 'after_or_equal:valid_from'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ];
    }
}
