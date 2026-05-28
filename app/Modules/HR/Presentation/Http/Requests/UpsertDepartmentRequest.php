<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertDepartmentRequest extends FormRequest
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
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'parent_id' => ['nullable', 'integer', 'min:1', 'exists:departments,id'],
            'manager_employee_id' => ['nullable', 'integer', 'min:1', 'exists:employees,id'],
            'department_name' => array_merge($required, ['string', 'max:160']),
            'department_code' => array_merge($required, ['string', 'max:50']),
            'depth' => ['nullable', 'integer', 'min:0'],
            'path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
