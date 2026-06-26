<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Users;

use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class SyncUserPermissionsRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return UserPermission::USERS_ASSIGN_PERMISSIONS; }

    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }
}
