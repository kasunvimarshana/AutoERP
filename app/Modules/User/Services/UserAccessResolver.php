<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\OrganizationUnitDirectoryInterface;
use Modules\Core\Contracts\OrganizationUnitUserAccessCheckerInterface;
use Modules\Core\Contracts\TenantUserAccessCheckerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Modules\User\Constants\UserStatus;
use Modules\User\Constants\UserSystemRole;

final class UserAccessResolver implements TenantUserAccessCheckerInterface, OrganizationUnitUserAccessCheckerInterface
{
    public function __construct(private readonly OrganizationUnitDirectoryInterface $organizationUnits)
    {
    }

    /** @var array<string, bool> */
    private array $superAdminCache = [];
    /** @var array<string, list<string>> */
    private array $effectiveNameCache = [];
    /** @var array<string, list<array{id:int,name:string,module:string,description:string|null}>> */
    private array $catalogueCache = [];

    public function isActiveTenantUser(int $userId, int $tenantId): bool
    {
        return $userId > 0 && $tenantId > 0 && DB::table('users')
            ->where('id', $userId)->where('tenant_id', $tenantId)
            ->where('status', UserStatus::ACTIVE)->whereNotNull('credentials_ready_at')
            ->whereNull('deleted_at')->exists();
    }

    /** @return list<int> */
    public function defaultOrganizationUnitIds(int $userId, int $tenantId): array
    {
        return $this->organizationUnitIds($userId, $tenantId, true);
    }

    /** @return list<int> */
    public function accessibleOrganizationUnitIds(int $userId, int $tenantId): array
    {
        return $this->organizationUnitIds($userId, $tenantId, false);
    }

    public function canAccessOrganizationUnit(int $userId, int $tenantId, int $organizationUnitId, bool $lockForUpdate = false): bool
    {
        if ($userId < 1 || $tenantId < 1 || $organizationUnitId < 1) {
            return false;
        }
        if (! $this->organizationUnits->isActive($tenantId, $organizationUnitId, $lockForUpdate)) {
            return false;
        }

        $query = DB::table('user_organization_units')
            ->join('users', function ($join): void {
                $join->on('users.id', '=', 'user_organization_units.user_id')
                    ->on('users.tenant_id', '=', 'user_organization_units.tenant_id');
            })
            ->where('user_organization_units.user_id', $userId)
            ->where('user_organization_units.tenant_id', $tenantId)
            ->where('user_organization_units.organization_unit_id', $organizationUnitId)
            ->where('user_organization_units.status', UserOrganizationUnitStatus::ACTIVE)
            ->where('users.status', UserStatus::ACTIVE)
            ->whereNotNull('users.credentials_ready_at')
            ->whereNull('users.deleted_at');

        return $lockForUpdate
            ? $query->select('user_organization_units.id')->lockForUpdate()->first() !== null
            : $query->exists();
    }

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        $permission = trim($permission);
        return $permission !== '' && in_array($permission, $this->effectivePermissionNames($userId, $tenantId), true);
    }

    public function isSuperAdmin(int $userId, int $tenantId): bool
    {
        if (! $this->isActiveTenantUser($userId, $tenantId)) {
            return false;
        }
        $key = $this->key($userId, $tenantId);
        return $this->superAdminCache[$key] ??= DB::table('user_roles')
            ->join('roles', function ($join): void {
                $join->on('roles.id', '=', 'user_roles.role_id')
                    ->on('roles.tenant_id', '=', 'user_roles.tenant_id');
            })
            ->where('user_roles.tenant_id', $tenantId)
            ->where('user_roles.user_id', $userId)
            ->where('roles.system_key', UserSystemRole::SUPER_ADMIN)
            ->where('roles.guard_name', UserGuard::TENANT_API)
            ->whereNull('roles.deleted_at')->exists();
    }

    /** @return list<string> */
    public function effectivePermissionNames(int $userId, int $tenantId): array
    {
        if (! $this->isActiveTenantUser($userId, $tenantId)) {
            return [];
        }
        $key = $this->key($userId, $tenantId);
        if (array_key_exists($key, $this->effectiveNameCache)) {
            return $this->effectiveNameCache[$key];
        }
        $permissions = $this->isSuperAdmin($userId, $tenantId)
            ? $this->activePermissionCatalogue($tenantId)
            : $this->assignedPermissions($userId, $tenantId);
        $names = array_values(array_unique(array_map(static fn (array $p): string => $p['name'], $permissions)));
        sort($names);
        return $this->effectiveNameCache[$key] = $names;
    }

    /** @return list<array{id:int,name:string,module:string,description:string|null}> */
    public function effectivePermissions(int $userId, int $tenantId): array
    {
        return $this->isSuperAdmin($userId, $tenantId)
            ? $this->activePermissionCatalogue($tenantId)
            : $this->assignedPermissions($userId, $tenantId);
    }

    /** @return list<string> */
    public function assignedPermissionNames(int $userId, int $tenantId): array
    {
        $names = array_map(static fn (array $p): string => $p['name'], $this->assignedPermissions($userId, $tenantId));
        sort($names);
        return array_values(array_unique($names));
    }

    /** @return list<array{id:int,name:string,module:string,description:string|null}> */
    public function assignedPermissions(int $userId, int $tenantId): array
    {
        if (! $this->isActiveTenantUser($userId, $tenantId)) {
            return [];
        }
        $rows = DB::table('permissions')
            ->where('permissions.tenant_id', $tenantId)
            ->where('permissions.guard_name', UserGuard::TENANT_API)
            ->where('permissions.is_active', true)
            ->where(function ($query) use ($userId, $tenantId): void {
                $query->whereExists(function ($direct) use ($userId, $tenantId): void {
                    $direct->selectRaw('1')->from('user_permissions')
                        ->whereColumn('user_permissions.permission_id', 'permissions.id')
                        ->whereColumn('user_permissions.tenant_id', 'permissions.tenant_id')
                        ->where('user_permissions.user_id', $userId)
                        ->where('user_permissions.tenant_id', $tenantId);
                })->orWhereExists(function ($throughRole) use ($userId, $tenantId): void {
                    $throughRole->selectRaw('1')->from('role_permissions')
                        ->join('roles', function ($join): void {
                            $join->on('roles.id', '=', 'role_permissions.role_id')
                                ->on('roles.tenant_id', '=', 'role_permissions.tenant_id');
                        })
                        ->join('user_roles', function ($join): void {
                            $join->on('user_roles.role_id', '=', 'roles.id')
                                ->on('user_roles.tenant_id', '=', 'roles.tenant_id');
                        })
                        ->whereColumn('role_permissions.permission_id', 'permissions.id')
                        ->whereColumn('role_permissions.tenant_id', 'permissions.tenant_id')
                        ->where('user_roles.user_id', $userId)
                        ->where('user_roles.tenant_id', $tenantId)
                        ->where('roles.guard_name', UserGuard::TENANT_API)
                        ->whereNull('roles.deleted_at');
                });
            })
            ->orderBy('module')->orderBy('name')
            ->get(['id', 'name', 'module', 'description']);

        return $rows->map(static fn (object $row): array => [
            'id' => (int) $row->id,
            'name' => (string) $row->name,
            'module' => (string) $row->module,
            'description' => $row->description === null ? null : (string) $row->description,
        ])->values()->all();
    }

    /** @return list<array{id:int,name:string,module:string,description:string|null}> */
    public function activePermissionCatalogue(int $tenantId): array
    {
        if ($tenantId < 1) {
            return [];
        }
        $key = 'catalogue:'.$tenantId;
        return $this->catalogueCache[$key] ??= DB::table('permissions')
            ->where('tenant_id', $tenantId)
            ->where('guard_name', UserGuard::TENANT_API)
            ->where('is_active', true)
            ->orderBy('module')->orderBy('name')
            ->get(['id', 'name', 'module', 'description'])
            ->map(static fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'module' => (string) $row->module,
                'description' => $row->description === null ? null : (string) $row->description,
            ])->values()->all();
    }

    public function isProtectedSuperAdminRole(mixed $role): bool
    {
        $key = $role instanceof DataRecord ? $role->get('system_key')
            : (is_array($role) ? ($role['system_key'] ?? null) : (is_object($role) ? ($role->system_key ?? null) : null));
        return $key === UserSystemRole::SUPER_ADMIN;
    }

    public function forgetForUserTenant(int $userId, int $tenantId): void
    {
        unset($this->superAdminCache[$this->key($userId, $tenantId)], $this->effectiveNameCache[$this->key($userId, $tenantId)]);
    }

    public function forgetForRoleTenant(int $roleId, int $tenantId): void
    {
        DB::table('user_roles')->where('tenant_id', $tenantId)->where('role_id', $roleId)
            ->pluck('user_id')->each(fn (mixed $id) => $this->forgetForUserTenant((int) $id, $tenantId));
    }

    public function forgetForTenant(int $tenantId): void
    {
        unset($this->catalogueCache['catalogue:'.$tenantId]);
        foreach (array_keys($this->effectiveNameCache + $this->superAdminCache) as $key) {
            if (str_ends_with($key, ':'.$tenantId)) {
                unset($this->effectiveNameCache[$key], $this->superAdminCache[$key]);
            }
        }
    }

    /** @return list<int> */
    private function organizationUnitIds(int $userId, int $tenantId, bool $defaultOnly): array
    {
        if (! $this->isActiveTenantUser($userId, $tenantId)) {
            return [];
        }
        $query = DB::table('user_organization_units')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('status', UserOrganizationUnitStatus::ACTIVE);
        if ($defaultOnly) {
            $query->where('default_marker', UserOrganizationUnitStatus::DEFAULT_MARKER);
        }

        $ids = $query->pluck('organization_unit_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        return $this->organizationUnits->activeIdsOrderedByPath($tenantId, $ids);
    }

    private function key(int $userId, int $tenantId): string
    {
        return $userId.':'.$tenantId;
    }
}
