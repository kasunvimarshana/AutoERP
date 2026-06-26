<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Users;

use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\AuthorizedUserRequest;

final class SyncUserOrganizationAccessRequest extends AuthorizedUserRequest
{
    protected function requiredPermission(): ?string { return UserPermission::USERS_MANAGE_ORGANIZATION_ACCESS; }

    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'organization_unit_ids' => ['required', 'array', 'min:1'],
            'organization_unit_ids.*' => ['integer', 'min:1', 'distinct'],
            'default_organization_unit_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
