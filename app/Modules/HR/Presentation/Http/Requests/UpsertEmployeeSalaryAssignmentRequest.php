<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertEmployeeSalaryAssignmentRequest extends FormRequest
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
            'salary_structure_id' => array_merge($required, ['integer', 'min:1', 'exists:salary_structures,id']),
            'effective_from' => array_merge($required, ['date']),
            'effective_to' => ['nullable', 'date'],
            'base_salary' => array_merge($required, ['numeric']),
            'pay_frequency' => ['nullable', 'string', 'max:255'],
            'created_by' => ['nullable', 'integer', 'min:0'],
        ];
    }
}