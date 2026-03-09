<?php

namespace App\Services;

use App\Domain\Models\User;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionService
{
    /**
     * Assign roles to a user, optionally scoped to a tenant (team).
     */
    public function assignRoles(string $userId, array $roles, ?string $teamId = null): User
    {
        $user = User::findOrFail($userId);

        if ($teamId !== null) {
            setPermissionsTeamId($teamId);
        }

        foreach ($roles as $role) {
            $user->assignRole($this->resolveRole($role, $teamId));
        }

        $this->clearPermissionCache($user);

        return $user->fresh(['roles', 'permissions']);
    }

    /**
     * Sync (replace) roles on a user.
     */
    public function syncRoles(string $userId, array $roles, ?string $teamId = null): User
    {
        $user = User::findOrFail($userId);

        if ($teamId !== null) {
            setPermissionsTeamId($teamId);
        }

        $resolvedRoles = array_map(fn ($r) => $this->resolveRole($r, $teamId), $roles);
        $user->syncRoles($resolvedRoles);

        $this->clearPermissionCache($user);

        return $user->fresh(['roles', 'permissions']);
    }

    /**
     * Sync permissions on a user.
     */
    public function syncPermissions(string $userId, array $permissions, ?string $teamId = null): User
    {
        $user = User::findOrFail($userId);

        if ($teamId !== null) {
            setPermissionsTeamId($teamId);
        }

        $resolvedPermissions = array_map(
            fn ($p) => $this->resolvePermission($p, $teamId),
            $permissions
        );
        $user->syncPermissions($resolvedPermissions);

        $this->clearPermissionCache($user);

        return $user->fresh(['roles', 'permissions']);
    }

    /**
     * Revoke a specific role from a user.
     */
    public function revokeRole(string $userId, string $role, ?string $teamId = null): User
    {
        $user = User::findOrFail($userId);

        if ($teamId !== null) {
            setPermissionsTeamId($teamId);
        }

        $user->removeRole($role);
        $this->clearPermissionCache($user);

        return $user->fresh(['roles']);
    }

    /**
     * Check if a user has a specific permission in a tenant context.
     */
    public function hasPermission(User $user, string $permission, ?string $teamId = null): bool
    {
        if ($teamId !== null) {
            setPermissionsTeamId($teamId);
        }

        return $user->hasPermissionTo($permission);
    }

    /**
     * Check if a user has any of the given roles.
     */
    public function hasAnyRole(User $user, array $roles, ?string $teamId = null): bool
    {
        if ($teamId !== null) {
            setPermissionsTeamId($teamId);
        }

        return $user->hasAnyRole($roles);
    }

    /**
     * Create a new role, optionally scoped to a team.
     */
    public function createRole(string $name, ?string $teamId = null, string $guardName = 'api'): Role
    {
        if ($teamId !== null) {
            setPermissionsTeamId($teamId);
        }

        return Role::firstOrCreate(
            ['name' => $name, 'guard_name' => $guardName],
            ['team_id' => $teamId]
        );
    }

    /**
     * Create a new permission.
     */
    public function createPermission(string $name, string $guardName = 'api'): Permission
    {
        return Permission::firstOrCreate([
            'name'       => $name,
            'guard_name' => $guardName,
        ]);
    }

    /**
     * Assign permissions to a role.
     */
    public function givePermissionsToRole(string $roleName, array $permissions, ?string $teamId = null): Role
    {
        if ($teamId !== null) {
            setPermissionsTeamId($teamId);
        }

        $role = Role::findByName($roleName, 'api');
        $role->givePermissionTo($permissions);

        return $role;
    }

    /**
     * Get all permissions grouped by resource.
     */
    public function getAllPermissionsGrouped(): array
    {
        return Cache::remember('permissions_grouped', 3600, function () {
            return Permission::all()
                ->groupBy(function ($permission) {
                    return explode('.', $permission->name)[0] ?? 'general';
                })
                ->map(fn ($perms) => $perms->pluck('name'))
                ->toArray();
        });
    }

    /**
     * Resolve a role by name or instance.
     */
    private function resolveRole(string|Role $role, ?string $teamId): Role
    {
        if ($role instanceof Role) {
            return $role;
        }

        return Role::findByName($role, 'api');
    }

    /**
     * Resolve a permission by name or instance.
     */
    private function resolvePermission(string|Permission $permission, ?string $teamId): Permission
    {
        if ($permission instanceof Permission) {
            return $permission;
        }

        return Permission::findByName($permission, 'api');
    }

    /**
     * Clear permission cache for a specific user.
     */
    private function clearPermissionCache(User $user): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
