<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPayslipRequest extends FormRequest
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
            'payroll_run_id' => array_merge($required, ['integer', 'min:1', 'exists:payroll_runs,id']),
            'salary_structure_id' => ['nullable', 'integer', 'min:1', 'exists:salary_structures,id'],
            'period_start' => array_merge($required, ['date']),
            'period_end' => array_merge($required, ['date']),
            'base_salary' => ['nullable', 'numeric'],
            'total_earnings' => ['nullable', 'numeric'],
            'total_deductions' => ['nullable', 'numeric'],
            'net_salary' => ['nullable', 'numeric'],
            'worked_days' => ['nullable', 'numeric'],
            'leave_days_paid' => ['nullable', 'numeric'],
            'leave_days_unpaid' => ['nullable', 'numeric'],
            'overtime_hours' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'max:255'],
            'journal_entry_id' => ['nullable', 'integer', 'min:1', 'exists:journal_entries,id'],
        ];
    }
}
