<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'device_token' => ['sometimes', 'required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'last_active_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
