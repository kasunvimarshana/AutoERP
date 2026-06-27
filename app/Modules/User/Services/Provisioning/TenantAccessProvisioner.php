<?php

declare(strict_types=1);

namespace Modules\User\Services\Provisioning;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Core\Contracts\TenantAggregateLockInterface;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserStatus;
use Modules\User\Constants\UserSystemRole;
use Modules\User\Models\PermissionModel;
use Modules\User\Models\RoleModel;
use Modules\User\Models\RolePermissionModel;

final class TenantAccessProvisioner implements TenantAccessProvisionerInterface
{
    public function __construct(
        private readonly PermissionDefinitionRegistryInterface $permissionDefinitions,
        private readonly TenantAggregateLockInterface $tenantLock,
    ) {}

    public function provision(int $tenantId): array
    {
        return DB::transaction(function () use ($tenantId): array {
            $this->tenantLock->lock($tenantId);
            $definitions = $this->permissionDefinitions->all();
            ksort($definitions);

            $permissionIds = [];
            foreach ($definitions as $name => $definition) {
                $permission = PermissionModel::query()->firstOrNew([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'guard_name' => UserGuard::TENANT_API,
                ]);
                $desired = [
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'guard_name' => UserGuard::TENANT_API,
                    'module' => (string) $definition['module'],
                    'description' => (string) $definition['description'],
                    'is_active' => true,
                ];

                if (! $permission->exists) {
                    $permission->forceFill([...$desired, 'row_version' => 1])->save();
                } elseif (
                    (string) $permission->getAttribute('module') !== $desired['module']
                    || (string) $permission->getAttribute('description') !== $desired['description']
                    || ! (bool) $permission->getAttribute('is_active')
                ) {
                    $permission->forceFill([
                        ...$desired,
                        'row_version' => max(1, (int) $permission->getAttribute('row_version')) + 1,
                    ])->save();
                }

                $permissionIds[] = (int) $permission->getKey();
            }

            PermissionModel::query()
                ->where('tenant_id', $tenantId)
                ->where('guard_name', UserGuard::TENANT_API)
                ->when($permissionIds !== [], fn ($query) => $query->whereNotIn('id', $permissionIds))
                ->where('is_active', true)
                ->update(['is_active' => false, 'row_version' => DB::raw('row_version + 1')]);

            $role = RoleModel::withTrashed()->firstOrNew([
                'tenant_id' => $tenantId,
                'system_key' => UserSystemRole::SUPER_ADMIN,
            ]);
            $roleValues = [
                'tenant_id' => $tenantId,
                'name' => UserSystemRole::SUPER_ADMIN_NAME,
                'active_name_key' => mb_strtolower(UserSystemRole::SUPER_ADMIN_NAME),
                'guard_name' => UserGuard::TENANT_API,
                'system_key' => UserSystemRole::SUPER_ADMIN,
                'is_system' => true,
                'description' => 'Protected tenant super administrator role.',
                'deleted_at' => null,
            ];
            if (! $role->exists) {
                $role->forceFill([...$roleValues, 'row_version' => 1])->save();
            } elseif (
                (string) $role->getAttribute('name') !== $roleValues['name']
                || (string) $role->getAttribute('active_name_key') !== $roleValues['active_name_key']
                || (string) $role->getAttribute('guard_name') !== $roleValues['guard_name']
                || ! (bool) $role->getAttribute('is_system')
                || (string) $role->getAttribute('description') !== $roleValues['description']
                || $role->getAttribute('deleted_at') !== null
            ) {
                $role->forceFill([
                    ...$roleValues,
                    'row_version' => max(1, (int) $role->getAttribute('row_version')) + 1,
                ])->save();
            }
            $roleId = (int) $role->getKey();

            RolePermissionModel::query()
                ->where('tenant_id', $tenantId)
                ->where('role_id', $roleId)
                ->when($permissionIds !== [], fn ($query) => $query->whereNotIn('permission_id', $permissionIds))
                ->delete();
            foreach ($permissionIds as $permissionId) {
                RolePermissionModel::query()->firstOrCreate([
                    'tenant_id' => $tenantId,
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], ['row_version' => 1]);
            }

            return ['role_id' => $roleId, 'permission_count' => count($permissionIds)];
        }, 3);
    }

    public function catalogueIsReady(int $tenantId, bool $lockForUpdate = false): bool
    {
        $expectedNames = array_keys($this->permissionDefinitions->all());
        sort($expectedNames);
        $query = PermissionModel::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_name', UserGuard::TENANT_API)
            ->where('is_active', true);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $actual = $query->pluck('name')->map(static fn ($name): string => (string) $name)->sort()->values()->all();

        return $actual === $expectedNames;
    }

    public function protectedSuperAdminRoleId(int $tenantId, bool $lockForUpdate = false): ?int
    {
        $query = RoleModel::query()
            ->where('tenant_id', $tenantId)
            ->where('system_key', UserSystemRole::SUPER_ADMIN)
            ->where('guard_name', UserGuard::TENANT_API)
            ->where('is_system', true);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $id = $query->value('id');

        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    }

    public function superAdminRoleIsReady(int $tenantId, int $roleId, bool $lockForUpdate = false): bool
    {
        $expectedNames = array_keys($this->permissionDefinitions->all());
        sort($expectedNames);
        $roleQuery = RoleModel::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($roleId)
            ->where('system_key', UserSystemRole::SUPER_ADMIN)
            ->where('guard_name', UserGuard::TENANT_API)
            ->where('is_system', true);
        if ($lockForUpdate) {
            $roleQuery->lockForUpdate();
        }
        if (! $roleQuery->exists()) {
            return false;
        }

        $assignedQuery = DB::table('role_permissions')
            ->join('permissions', function ($join): void {
                $join->on('permissions.id', '=', 'role_permissions.permission_id')
                    ->on('permissions.tenant_id', '=', 'role_permissions.tenant_id');
            })
            ->where('role_permissions.tenant_id', $tenantId)
            ->where('role_permissions.role_id', $roleId)
            ->where('permissions.is_active', true)
            ->where('permissions.guard_name', UserGuard::TENANT_API)
            ->orderBy('permissions.name');
        if ($lockForUpdate) {
            $assignedQuery->lockForUpdate();
        }
        $assignedNames = $assignedQuery->pluck('permissions.name')->map(static fn ($name): string => (string) $name)->all();

        return $assignedNames === $expectedNames;
    }

    public function isReady(int $tenantId, int $roleId, bool $lockForUpdate = false): bool
    {
        return $this->catalogueIsReady($tenantId, $lockForUpdate)
            && $this->superAdminRoleIsReady($tenantId, $roleId, $lockForUpdate);
    }

    public function permissionCount(int $tenantId): int
    {
        return PermissionModel::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_name', UserGuard::TENANT_API)
            ->where('is_active', true)
            ->count();
    }

    public function hasOperationalAdministrator(
        int $tenantId,
        int $userId,
        int $rootOrganizationUnitId,
        int $superAdminRoleId,
        bool $lockForUpdate = false,
    ): bool {
        if (! $this->isReady($tenantId, $superAdminRoleId, $lockForUpdate)) {
            return false;
        }
        $activeUser = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('id', $userId)
            ->where('status', UserStatus::ACTIVE)
            ->whereNotNull('credentials_ready_at')
            ->whereNull('deleted_at')
            ->when($lockForUpdate, static fn ($query) => $query->lockForUpdate())
            ->exists();
        if (! $activeUser) {
            return false;
        }
        $roleAssigned = DB::table('user_roles')
            ->where('tenant_id', $tenantId)->where('user_id', $userId)
            ->where('role_id', $superAdminRoleId)
            ->when($lockForUpdate, static fn ($query) => $query->lockForUpdate())
            ->exists();
        $rootAssigned = DB::table('user_organization_units')
            ->where('tenant_id', $tenantId)->where('user_id', $userId)
            ->where('organization_unit_id', $rootOrganizationUnitId)
            ->where('status', 'active')->where('is_default', true)
            ->when($lockForUpdate, static fn ($query) => $query->lockForUpdate())
            ->exists();

        return $roleAssigned && $rootAssigned;
    }

}
