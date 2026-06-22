<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\DTOs\DataRecord;
use Modules\User\Constants\UserPermission;
use Modules\User\Constants\UserTenantStatus;

final class UserAccessResolver
{
    /**
     * @var array<string, bool>
     */
    private array $superAdminCache = [];

    /**
     * @var array<int, bool>
     */
    private array $platformOperatorCache = [];

    /**
     * @var array<string, list<string>>
     */
    private array $effectiveNameCache = [];

    /**
     * @var array<string, list<string>>
     */
    private array $assignedNameCache = [];

    /**
     * @var array<string, list<array{id:int,name:string,module:string|null,description:string|null}>>
     */
    private array $catalogueCache = [];

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        $permission = trim($permission);
        if ($userId < 1 || $tenantId < 1 || $permission === '') {
            return false;
        }

        return in_array($permission, $this->effectivePermissionNames($userId, $tenantId), true);
    }

    public function isSuperAdmin(int $userId, int $tenantId): bool
    {
        if ($userId < 1 || $tenantId < 1) {
            return false;
        }

        $key = $this->userTenantKey($userId, $tenantId);
        if (array_key_exists($key, $this->superAdminCache)) {
            return $this->superAdminCache[$key];
        }

        $this->superAdminCache[$key] = DB::table('user_roles')
            ->join('roles', function ($join): void {
                $join->on('roles.id', '=', 'user_roles.role_id')
                    ->on('roles.tenant_id', '=', 'user_roles.tenant_id');
            })
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->where('user_roles.tenant_id', $tenantId)
            ->where('user_roles.user_id', $userId)
            ->where('users.status', 'active')
            ->whereExists(function ($membership) use ($tenantId, $userId): void {
                $membership->selectRaw('1')
                    ->from('user_tenants')
                    ->whereColumn('user_tenants.user_id', 'users.id')
                    ->where('user_tenants.tenant_id', $tenantId)
                    ->where('user_tenants.user_id', $userId)
                    ->where('user_tenants.status', UserTenantStatus::ACTIVE);
            })
            ->where('roles.name', UserPermission::SUPER_ADMIN_ROLE)
            ->whereNull('users.deleted_at')
            ->whereNull('roles.deleted_at')
            ->exists();

        return $this->superAdminCache[$key];
    }

    public function isPlatformOperator(int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }

        if (array_key_exists($userId, $this->platformOperatorCache)) {
            return $this->platformOperatorCache[$userId];
        }

        $this->platformOperatorCache[$userId] = DB::table('users')
            ->where('id', $userId)
            ->where('status', 'active')
            ->where('is_platform_operator', true)
            ->whereNull('deleted_at')
            ->exists();

        return $this->platformOperatorCache[$userId];
    }

    /**
     * @return list<string>
     */
    public function effectivePermissionNames(int $userId, int $tenantId): array
    {
        if ($userId < 1 || $tenantId < 1) {
            return [];
        }

        $key = $this->userTenantKey($userId, $tenantId);
        if (array_key_exists($key, $this->effectiveNameCache)) {
            return $this->effectiveNameCache[$key];
        }

        $names = $this->isSuperAdmin($userId, $tenantId)
            ? array_map(
                static fn (array $permission): string => $permission['name'],
                $this->activePermissionCatalogue($tenantId),
            )
            : $this->assignedPermissionNames($userId, $tenantId);

        if (! $this->isPlatformOperator($userId)) {
            $names = array_values(array_filter(
                $names,
                static fn (string $name): bool => ! str_starts_with($name, 'tenant.platform.'),
            ));
        }

        sort($names);
        $this->effectiveNameCache[$key] = array_values(array_unique($names));

        return $this->effectiveNameCache[$key];
    }

    /**
     * @return list<array{id:int,name:string,module:string|null,description:string|null}>
     */
    public function effectivePermissions(int $userId, int $tenantId): array
    {
        if ($userId < 1 || $tenantId < 1) {
            return [];
        }

        $permissions = $this->isSuperAdmin($userId, $tenantId)
            ? $this->activePermissionCatalogue($tenantId)
            : $this->assignedPermissions($userId, $tenantId);

        if ($this->isPlatformOperator($userId)) {
            return $permissions;
        }

        return array_values(array_filter(
            $permissions,
            static fn (array $permission): bool => ! str_starts_with(
                $permission['name'],
                'tenant.platform.',
            ),
        ));
    }

    /**
     * @return list<string>
     */
    public function assignedPermissionNames(int $userId, int $tenantId): array
    {
        if ($userId < 1 || $tenantId < 1) {
            return [];
        }

        $key = $this->userTenantKey($userId, $tenantId);
        if (array_key_exists($key, $this->assignedNameCache)) {
            return $this->assignedNameCache[$key];
        }

        $names = array_map(
            static fn (array $permission): string => $permission['name'],
            $this->assignedPermissions($userId, $tenantId),
        );
        sort($names);
        $this->assignedNameCache[$key] = array_values(array_unique($names));

        return $this->assignedNameCache[$key];
    }

    /**
     * @return list<array{id:int,name:string,module:string|null,description:string|null}>
     */
    public function assignedPermissions(int $userId, int $tenantId): array
    {
        if ($userId < 1 || $tenantId < 1) {
            return [];
        }

        $permissions = [];
        foreach ($this->directPermissions($userId, $tenantId) as $permission) {
            $permissions[$permission['id']] = $permission;
        }

        foreach ($this->rolePermissions($userId, $tenantId) as $permission) {
            $permissions[$permission['id']] = $permission;
        }

        usort(
            $permissions,
            static fn (array $left, array $right): int => [$left['module'] ?? '', $left['name']]
                <=> [$right['module'] ?? '', $right['name']],
        );

        return array_values($permissions);
    }

    /**
     * @return list<array{id:int,name:string,module:string|null,description:string|null}>
     */
    public function activePermissionCatalogue(int $tenantId): array
    {
        if ($tenantId < 1) {
            return [];
        }

        $key = 'catalogue:'.$tenantId;
        if (array_key_exists($key, $this->catalogueCache)) {
            return $this->catalogueCache[$key];
        }

        $this->catalogueCache[$key] = DB::table('permissions')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('module')
            ->orderBy('name')
            ->get(['id', 'name', 'module', 'description'])
            ->map(static fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'module' => $row->module !== null ? (string) $row->module : null,
                'description' => $row->description !== null ? (string) $row->description : null,
            ])
            ->values()
            ->all();

        return $this->catalogueCache[$key];
    }

    public function isProtectedSuperAdminRole(mixed $role): bool
    {
        $name = null;
        if ($role instanceof DataRecord) {
            $name = $role->get('name');
        } elseif (is_array($role)) {
            $name = $role['name'] ?? null;
        } elseif (is_object($role)) {
            $name = $role->name ?? null;
        }

        return is_scalar($name) && trim((string) $name) === UserPermission::SUPER_ADMIN_ROLE;
    }

    public function forgetForUserTenant(int $userId, int $tenantId): void
    {
        $key = $this->userTenantKey($userId, $tenantId);

        unset(
            $this->superAdminCache[$key],
            $this->effectiveNameCache[$key],
            $this->assignedNameCache[$key],
            $this->platformOperatorCache[$userId],
        );
    }

    public function forgetForRoleTenant(int $roleId, int $tenantId): void
    {
        if ($roleId < 1 || $tenantId < 1) {
            return;
        }

        DB::table('user_roles')
            ->where('tenant_id', $tenantId)
            ->where('role_id', $roleId)
            ->pluck('user_id')
            ->each(function (mixed $userId) use ($tenantId): void {
                $this->forgetForUserTenant((int) $userId, $tenantId);
            });
    }

    public function forgetForTenant(int $tenantId): void
    {
        unset($this->catalogueCache['catalogue:'.$tenantId]);

        foreach (array_keys($this->superAdminCache) as $key) {
            if (str_ends_with($key, ':'.$tenantId)) {
                unset($this->superAdminCache[$key]);
            }
        }

        foreach (array_keys($this->effectiveNameCache) as $key) {
            if (str_ends_with($key, ':'.$tenantId)) {
                unset($this->effectiveNameCache[$key]);
            }
        }

        foreach (array_keys($this->assignedNameCache) as $key) {
            if (str_ends_with($key, ':'.$tenantId)) {
                unset($this->assignedNameCache[$key]);
            }
        }
    }

    /**
     * @return list<array{id:int,name:string,module:string|null,description:string|null}>
     */
    private function directPermissions(int $userId, int $tenantId): array
    {
        return DB::table('user_permissions')
            ->join('users', 'users.id', '=', 'user_permissions.user_id')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_permissions.tenant_id', $tenantId)
            ->where('user_permissions.user_id', $userId)
            ->where('users.status', 'active')
            ->whereExists(function ($membership) use ($tenantId, $userId): void {
                $membership->selectRaw('1')
                    ->from('user_tenants')
                    ->whereColumn('user_tenants.user_id', 'users.id')
                    ->where('user_tenants.tenant_id', $tenantId)
                    ->where('user_tenants.user_id', $userId)
                    ->where('user_tenants.status', UserTenantStatus::ACTIVE);
            })
            ->where('permissions.tenant_id', $tenantId)
            ->whereNull('users.deleted_at')
            ->whereNull('permissions.deleted_at')
            ->orderBy('permissions.module')
            ->orderBy('permissions.name')
            ->get(['permissions.id', 'permissions.name', 'permissions.module', 'permissions.description'])
            ->map(static fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'module' => $row->module !== null ? (string) $row->module : null,
                'description' => $row->description !== null ? (string) $row->description : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id:int,name:string,module:string|null,description:string|null}>
     */
    private function rolePermissions(int $userId, int $tenantId): array
    {
        return DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->join('roles', function ($join): void {
                $join->on('roles.id', '=', 'role_permissions.role_id')
                    ->on('roles.tenant_id', '=', 'role_permissions.tenant_id');
            })
            ->join('user_roles', function ($join): void {
                $join->on('user_roles.role_id', '=', 'roles.id')
                    ->on('user_roles.tenant_id', '=', 'roles.tenant_id');
            })
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->where('role_permissions.tenant_id', $tenantId)
            ->where('user_roles.user_id', $userId)
            ->where('users.status', 'active')
            ->whereExists(function ($membership) use ($tenantId, $userId): void {
                $membership->selectRaw('1')
                    ->from('user_tenants')
                    ->whereColumn('user_tenants.user_id', 'users.id')
                    ->where('user_tenants.tenant_id', $tenantId)
                    ->where('user_tenants.user_id', $userId)
                    ->where('user_tenants.status', UserTenantStatus::ACTIVE);
            })
            ->where('permissions.tenant_id', $tenantId)
            ->whereNull('users.deleted_at')
            ->whereNull('roles.deleted_at')
            ->whereNull('permissions.deleted_at')
            ->orderBy('permissions.module')
            ->orderBy('permissions.name')
            ->get(['permissions.id', 'permissions.name', 'permissions.module', 'permissions.description'])
            ->map(static fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'module' => $row->module !== null ? (string) $row->module : null,
                'description' => $row->description !== null ? (string) $row->description : null,
            ])
            ->values()
            ->all();
    }

    private function userTenantKey(int $userId, int $tenantId): string
    {
        return $userId.':'.$tenantId;
    }
}
