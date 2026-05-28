<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertAttendanceLogRequest extends FormRequest
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
            'biometric_device_id' => ['nullable', 'integer', 'min:1', 'exists:biometric_devices,id'],
            'punch_time' => array_merge($required, ['date']),
            'punch_type' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'raw_data' => ['nullable', 'array'],
            'processed_at' => ['nullable', 'date'],
        ];
    }
}
