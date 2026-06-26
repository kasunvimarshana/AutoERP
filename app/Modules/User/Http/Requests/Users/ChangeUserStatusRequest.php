<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Users;

use Illuminate\Validation\Rule;
use Modules\User\Constants\UserPermission;
use Modules\User\Constants\UserStatus;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class ChangeUserStatusRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string
    {
        return $this->input('status') === UserStatus::ACTIVE
            ? UserPermission::USERS_ACTIVATE
            : UserPermission::USERS_DEACTIVATE;
    }

    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(UserStatus::mutableValues())],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
