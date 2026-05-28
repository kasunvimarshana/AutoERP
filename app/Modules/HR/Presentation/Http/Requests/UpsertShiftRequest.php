<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertShiftRequest extends FormRequest
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
            'name' => array_merge($required, ['string', 'max:255']),
            'code' => array_merge($required, ['string', 'max:255']),
            'shift_type' => ['nullable', 'string', 'max:255'],
            'start_time' => array_merge($required, ['date_format:H:i:s']),
            'end_time' => array_merge($required, ['date_format:H:i:s']),
            'break_duration' => ['nullable', 'integer'],
            'grace_minutes' => ['nullable', 'integer'],
            'overtime_threshold' => ['nullable', 'integer'],
            'work_days' => ['nullable', 'array'],
            'is_night_shift' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'created_by' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
