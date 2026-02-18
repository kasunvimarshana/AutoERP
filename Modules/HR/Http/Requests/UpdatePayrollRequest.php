<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $payrollId = $this->route('payroll');

        return [
            'employee_id' => ['sometimes', 'required', 'integer', 'exists:employees,id'],
            'payroll_number' => ['nullable', 'string', 'max:50', "unique:payrolls,payroll_number,{$payrollId}"],
            'month' => ['sometimes', 'required', 'integer', 'min:1', 'max:12'],
            'year' => ['sometimes', 'required', 'integer', 'min:2000', 'max:2100'],
            'basic_salary' => ['sometimes', 'required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'gross_salary' => ['nullable', 'numeric', 'min:0'],
            'net_salary' => ['nullable', 'numeric', 'min:0'],
            'allowance_details' => ['nullable', 'array'],
            'deduction_details' => ['nullable', 'array'],
            'status' => ['sometimes', 'required', 'in:draft,processed,paid,cancelled'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
