<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertLeaveApplicationRequest extends FormRequest
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
            'start_date' => array_merge($required, ['date']),
            'end_date' => array_merge($required, ['date']),
            'total_days' => ['nullable', 'numeric'],
            'half_day_type' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
            'approver_id' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'approver_note' => ['nullable', 'string'],
            'approved_at' => ['nullable', 'date'],
            'attachment_path' => ['nullable', 'string', 'max:255'],
            'created_by' => ['nullable', 'integer', 'min:0'],
        ];
    }
}