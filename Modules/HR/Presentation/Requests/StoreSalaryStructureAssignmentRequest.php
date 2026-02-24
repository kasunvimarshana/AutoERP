<?php

namespace Modules\HR\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryStructureAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'    => ['required', 'string'],
            'structure_id'   => ['required', 'string'],
            'base_amount'    => ['required', 'numeric', 'min:0.00000001'],
            'effective_from' => ['nullable', 'date'],
        ];
    }
}
