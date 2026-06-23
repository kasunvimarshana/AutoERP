<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\User\Constants\UserPermission;
use Modules\User\Http\Requests\Concerns\AuthorizesUserPermission;

final class ListUserEntityRequest extends TenantScopedRequest
{
    use AuthorizesUserPermission;

    public function authorize(): bool
    {
        return match ($this->route()?->getName()) {
            'user.users.index' => $this->canUse(UserPermission::USERS_VIEW),
            'user.roles.index' => $this->canUse(UserPermission::ROLES_VIEW),
            'user.permissions.index' => $this->canUse(UserPermission::PERMISSIONS_VIEW),
            'user.role-permissions.index' => $this->canUse(UserPermission::ROLES_VIEW),
            'user.user-roles.index' => $this->canUse(UserPermission::USERS_VIEW),
            'user.user-permissions.index' => $this->canUse(UserPermission::USERS_VIEW),
            'user.user-tenants.index' => $this->canUse(UserPermission::USERS_VIEW),
            default => auth()->check(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_filter_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'role_id' => ['nullable', 'integer', 'min:1'],
            'permission_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:80'],
            'search' => ['nullable', 'string', 'max:255'],
            'module' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
