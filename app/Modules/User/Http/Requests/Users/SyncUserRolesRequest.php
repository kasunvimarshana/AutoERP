<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Users;

use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class SyncUserRolesRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return UserPermission::USERS_ASSIGN_ROLES; }

    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'role_ids' => ['present', 'array'],
            'role_ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }
}
