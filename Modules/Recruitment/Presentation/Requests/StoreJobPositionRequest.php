<?php

namespace Modules\Recruitment\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'department_id'       => ['nullable', 'uuid'],
            'location'            => ['nullable', 'string', 'max:255'],
            'employment_type'     => ['nullable', 'in:full_time,part_time,contract,internship'],
            'description'         => ['nullable', 'string'],
            'requirements'        => ['nullable', 'string'],
            'vacancies'           => ['nullable', 'integer', 'min:1'],
            'expected_start_date' => ['nullable', 'date'],
        ];
    }
}
