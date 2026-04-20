<?php

declare(strict_types=1);

namespace Modules\Employee\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|min:1',
            'user_id' => 'nullable|integer|min:1',
            'org_unit_id' => 'nullable|integer|min:1',
            'employee_code' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
            'include' => 'nullable|string|max:255',
        ];
    }
}
