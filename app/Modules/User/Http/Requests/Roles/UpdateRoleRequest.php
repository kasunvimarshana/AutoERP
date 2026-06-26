<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Roles;

use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class UpdateRoleRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return UserPermission::ROLES_UPDATE; }
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'permission_ids' => ['prohibited'],
            'guard_name' => ['prohibited'],
            'is_system' => ['prohibited'],
            'system_key' => ['prohibited'],
        ];
    }
}
