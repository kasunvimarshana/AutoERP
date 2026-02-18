<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Employee Request
 *
 * Validates data for updating an employee.
 */
class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employeeId = $this->route('employee');

        return [
            'employee_number' => ['nullable', 'string', 'max:50', "unique:employees,employee_number,{$employeeId}"],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', "unique:employees,email,{$employeeId}"],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'reports_to' => ['nullable', 'integer', 'exists:employees,id'],
            'hire_date' => ['sometimes', 'required', 'date'],
            'termination_date' => ['nullable', 'date', 'after:hire_date'],
            'employment_type' => ['sometimes', 'required', 'in:full-time,part-time,contract,intern'],
            'status' => ['sometimes', 'required', 'in:active,inactive,terminated,on-leave'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'emergency_contact' => ['nullable', 'array'],
        ];
    }
}
