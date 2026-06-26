<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Users;

use Illuminate\Validation\Rule;
use Modules\User\Constants\UserPermission;
use Modules\User\Constants\UserStatus;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class ListUsersRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return UserPermission::USERS_VIEW; }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(UserStatus::values())],
            'role_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_filter_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
