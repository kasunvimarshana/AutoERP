<?php

namespace Modules\HR\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePerformanceGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'period'      => ['required', 'string', 'in:q1,q2,q3,q4,annual,monthly,custom'],
            'year'        => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'due_date'    => ['nullable', 'date'],
        ];
    }
}
