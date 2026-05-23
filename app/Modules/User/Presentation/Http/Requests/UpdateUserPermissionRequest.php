<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPermissionRequest extends FormRequest
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
            'permission_id' => ['sometimes', 'required', 'integer', 'exists:permissions,id'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
