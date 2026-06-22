<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Constants\UserPermission;
use Modules\User\Constants\UserTenantStatus;
use Modules\User\Http\Requests\Concerns\AuthorizesUserPermission;

final class UpsertUserTenantRequest extends FormRequest
{
    use AuthorizesUserPermission;

    public function authorize(): bool
    {
        return $this->canUse(UserPermission::USERS_MANAGE_ORGANIZATION_ACCESS);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => [...$required, 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:organization_units,id',
            ],
            'metadata' => ['nullable', 'array'],
            'user_id' => [...$required, 'integer', 'min:1', 'exists:users,id'],
            'role_id' => ['nullable', 'integer', 'min:1', 'exists:roles,id'],
            'status' => ['sometimes', 'string', Rule::in(UserTenantStatus::values())],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
