<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Users;

use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class UpdateUserProfileRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return UserPermission::USERS_UPDATE; }

    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'username' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'email' => ['prohibited'],
            'status' => ['prohibited'],
            'password' => ['prohibited'],
            'role_ids' => ['prohibited'],
            'organization_unit_ids' => ['prohibited'],
            'default_organization_unit_id' => ['prohibited'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
