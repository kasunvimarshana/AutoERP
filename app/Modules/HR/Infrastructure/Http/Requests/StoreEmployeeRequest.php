<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'       => 'required|integer',
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'email'           => 'required|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'date_of_birth'   => 'nullable|date',
            'gender'          => 'nullable|string|in:male,female,other',
            'address'         => 'nullable|string',
            'employee_number' => 'required|string|max:50',
            'hire_date'       => 'required|date',
            'employment_type' => 'required|string|in:full_time,part_time,contract,intern',
            'status'          => 'nullable|string|in:active,inactive,on_leave,terminated',
            'department_id'   => 'nullable|integer',
            'position_id'     => 'nullable|integer',
            'manager_id'      => 'nullable|integer',
            'salary'          => 'nullable|numeric|min:0',
            'currency'        => 'nullable|string|size:3',
            'org_unit_id'     => 'nullable|integer',
            'metadata'        => 'nullable|array',
            'is_active'       => 'boolean',
            'user_id'         => 'nullable|integer',
        ];
    }
}
