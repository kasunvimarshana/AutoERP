<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Users;

use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class CreateUserRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return UserPermission::USERS_CREATE; }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role_ids' => ['present', 'array'],
            'role_ids.*' => ['integer', 'min:1', 'distinct'],
            'organization_unit_ids' => ['required', 'array', 'min:1'],
            'organization_unit_ids.*' => ['integer', 'min:1', 'distinct'],
            'default_organization_unit_id' => ['required', 'integer', 'min:1'],
            'password' => ['prohibited'],
            'password_confirmation' => ['prohibited'],
            'status' => ['prohibited'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
