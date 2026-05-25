<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertLeaveAllocationRequest extends FormRequest
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
            'leave_type_id' => array_merge($required, ['integer', 'min:1', 'exists:leave_types,id']),
            'year' => array_merge($required, ['integer', 'min:0']),
            'allocated_days' => ['nullable', 'numeric'],
            'used_days' => ['nullable', 'numeric'],
            'pending_days' => ['nullable', 'numeric'],
            'carried_forward' => ['nullable', 'numeric'],
            'expiry_date' => ['nullable', 'date'],
            'created_by' => ['nullable', 'integer', 'min:0'],
        ];
    }
}