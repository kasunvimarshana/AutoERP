<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\User\Constants\UserPermission;

final class UserAuthorizationService
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    public function canCurrent(string $permission): bool
    {
        $userId = $this->currentUser->currentUserId();
        $tenantId = $this->currentTenant->currentTenantId() ?? $this->currentUser->currentTenantId();

        if ($userId === null || $tenantId === null) {
            return false;
        }

        return $this->can($userId, $tenantId, $permission);
    }

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        if (! in_array($permission, UserPermission::values(), true)) {
            return false;
        }

        $roleIds = DB::table('user_roles')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->pluck('role_id');

        if ($roleIds->isNotEmpty() && DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $roleIds)
            ->where('name', UserPermission::SUPER_ADMIN_ROLE)
            ->whereNull('deleted_at')
            ->exists()) {
            return true;
        }

        if (DB::table('user_permissions')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_permissions.tenant_id', $tenantId)
            ->where('user_permissions.user_id', $userId)
            ->where('permissions.name', $permission)
            ->whereNull('permissions.deleted_at')
            ->exists()) {
            return true;
        }

        if ($roleIds->isEmpty()) {
            return false;
        }

        return DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.tenant_id', $tenantId)
            ->whereIn('role_permissions.role_id', $roleIds)
            ->where('permissions.name', $permission)
            ->whereNull('permissions.deleted_at')
            ->exists();
    }
}
