<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Users;

use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class VersionedUserActionRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string
    {
        return UserPermission::USERS_DELETE;
    }

    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
