<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Roles;

use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class ListRolesRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return UserPermission::ROLES_VIEW; }
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
