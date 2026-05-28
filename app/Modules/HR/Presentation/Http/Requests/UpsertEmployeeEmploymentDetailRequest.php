<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HR\Domain\Constants\EmployeeStatus;

final class UpsertEmployeeEmploymentDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'min:1', 'exists:departments,id'],
            'designation_id' => ['nullable', 'integer', 'min:1', 'exists:designations,id'],
            'employment_type_id' => ['nullable', 'integer', 'min:1', 'exists:employment_types,id'],
            'employment_status' => ['nullable', 'string', 'in:' . implode(',', EmployeeStatus::values())],
            'joining_date' => ['nullable', 'date'],
            'probation_end_date' => ['nullable', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'leaving_date' => ['nullable', 'date'],
            'reporting_manager_id' => ['nullable', 'integer', 'min:1', 'exists:employees,id'],
            'work_location_id' => ['nullable', 'integer', 'min:1'],
            'shift_id' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
