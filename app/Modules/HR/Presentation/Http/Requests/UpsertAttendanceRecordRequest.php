<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'employee_id' => array_merge($required, ['integer', 'min:1', 'exists:employees,id']),
            'attendance_date' => array_merge($required, ['date']),
            'check_in' => ['nullable', 'date'],
            'check_out' => ['nullable', 'date'],
            'break_duration' => ['nullable', 'integer'],
            'worked_minutes' => ['nullable', 'integer'],
            'overtime_minutes' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:255'],
            'shift_id' => ['nullable', 'integer', 'min:1', 'exists:shifts,id'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
