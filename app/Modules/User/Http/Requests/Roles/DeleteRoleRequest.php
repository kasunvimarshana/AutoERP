<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Roles;

use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class DeleteRoleRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return UserPermission::ROLES_DELETE; }
    public function rules(): array { return ['expected_version' => ['required', 'integer', 'min:1']]; }
}
