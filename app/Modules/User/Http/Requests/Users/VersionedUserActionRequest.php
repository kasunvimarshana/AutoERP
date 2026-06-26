<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Users;

use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class VersionedUserActionRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string
    {
        return $this->routeIs('api.v1.user.users.destroy')
            ? UserPermission::USERS_DELETE
            : UserPermission::USERS_MANAGE_INVITATIONS;
    }

    public function rules(): array
    {
        $isArchive = $this->routeIs('api.v1.user.users.destroy');

        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'reason' => $isArchive
                ? ['required', 'string', 'min:3', 'max:500']
                : ['prohibited'],
        ];
    }
}
