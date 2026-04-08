<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'      => 'sometimes|required|string|max:100',
            'last_name'       => 'sometimes|required|string|max:100',
            'email'           => 'sometimes|required|email|max:255',
            'employee_number' => 'sometimes|required|string|max:50',
            'hire_date'       => 'sometimes|required|date',
            'employment_type' => 'sometimes|required|string|in:full_time,part_time,contract,intern',
            'phone'           => 'nullable|string|max:50',
            'date_of_birth'   => 'nullable|date',
            'gender'          => 'nullable|string|in:male,female,other',
            'address'         => 'nullable|string',
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
