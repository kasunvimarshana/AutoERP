<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRolePermissionRequest extends FormRequest
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
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'permission_id' => [
                'required',
                'integer',
                'exists:permissions,id',
                Rule::unique('role_permissions')->where(fn ($query) => $query
                    ->where('tenant_id', $this->input('tenant_id'))
                    ->where('role_id', $this->input('role_id'))
                    ->where('permission_id', $this->input('permission_id'))),
            ],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
