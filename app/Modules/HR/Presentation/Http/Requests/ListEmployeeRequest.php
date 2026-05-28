<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListEmployeeRequest extends FormRequest
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
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('hr.pagination.max_per_page', 200)],
            'employee_code' => ['nullable', 'string', 'max:60'],
            'search' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'designation_id' => ['nullable', 'integer', 'min:1'],
            'employment_type_id' => ['nullable', 'integer', 'min:1'],
            'employment_status' => ['nullable', 'string', 'max:60'],
            'is_active' => ['nullable', 'boolean'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
        ];
    }
}
