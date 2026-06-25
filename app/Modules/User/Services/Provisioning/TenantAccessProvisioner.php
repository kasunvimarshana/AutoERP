<?php

declare(strict_types=1);

namespace Modules\User\Services\Provisioning;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Tenant\Services\Contracts\TenantAccessProvisionerInterface;
use Modules\User\Constants\UserPermission;
use Modules\User\Models\PermissionModel;
use Modules\User\Models\RoleModel;

final class TenantAccessProvisioner implements TenantAccessProvisionerInterface
{
    private const GUARD_NAME = 'api';
    private const SYSTEM_DEFINED = 'system_defined';

    public function __construct(
        private readonly PermissionDefinitionRegistryInterface $permissionDefinitions,
    ) {}

    public function provision(int $tenantId): array
    {
        return DB::transaction(function () use ($tenantId): array {
            $definitions = $this->permissionDefinitions->all();
            ksort($definitions);

            $permissionIds = [];
            foreach ($definitions as $name => $definition) {
                $permission = PermissionModel::withTrashed()
                    ->where('tenant_id', $tenantId)
                    ->where('name', $name)
                    ->where('guard_name', self::GUARD_NAME)
                    ->first();
                if ($permission instanceof PermissionModel) {
                    $permission->forceFill([
                        'module' => $definition['module'],
                        'description' => $definition['description'],
                        'metadata' => [self::SYSTEM_DEFINED => true],
                        'deleted_at' => null,
                        'row_version' => max(1, (int) $permission->getAttribute('row_version')) + 1,
                    ])->save();
                } else {
                    $permission = PermissionModel::query()->create([
                        'tenant_id' => $tenantId,
                        'name' => $name,
                        'guard_name' => self::GUARD_NAME,
                        'module' => $definition['module'],
                        'description' => $definition['description'],
                        'metadata' => [self::SYSTEM_DEFINED => true],
                        'row_version' => 1,
                    ]);
                }
                $permissionIds[] = (int) $permission->getKey();
            }

            $stalePermissionIds = PermissionModel::query()
                ->where('tenant_id', $tenantId)
                ->where('guard_name', self::GUARD_NAME)
                ->where('metadata->'.self::SYSTEM_DEFINED, true)
                ->when($permissionIds !== [], fn ($query) => $query->whereNotIn('id', $permissionIds))
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            if ($stalePermissionIds !== []) {
                DB::table('role_permissions')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('permission_id', $stalePermissionIds)
                    ->delete();
                PermissionModel::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('id', $stalePermissionIds)
                    ->delete();
            }

            $role = RoleModel::withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('name', UserPermission::SUPER_ADMIN_ROLE)
                ->where('guard_name', self::GUARD_NAME)
                ->first();
            if ($role instanceof RoleModel) {
                $role->forceFill([
                    'description' => 'Protected tenant super administrator role.',
                    'metadata' => [self::SYSTEM_DEFINED => true, 'protected' => true],
                    'deleted_at' => null,
                    'row_version' => max(1, (int) $role->getAttribute('row_version')) + 1,
                ])->save();
            } else {
                $role = RoleModel::query()->create([
                    'tenant_id' => $tenantId,
                    'name' => UserPermission::SUPER_ADMIN_ROLE,
                    'guard_name' => self::GUARD_NAME,
                    'description' => 'Protected tenant super administrator role.',
                    'metadata' => [self::SYSTEM_DEFINED => true, 'protected' => true],
                    'row_version' => 1,
                ]);
            }
            $roleId = (int) $role->getKey();

            DB::table('role_permissions')
                ->where('tenant_id', $tenantId)
                ->where('role_id', $roleId)
                ->when($permissionIds !== [], fn ($query) => $query->whereNotIn('permission_id', $permissionIds))
                ->delete();

            $assignedIds = DB::table('role_permissions')
                ->where('tenant_id', $tenantId)
                ->where('role_id', $roleId)
                ->pluck('permission_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            $now = now();
            $rows = [];
            foreach (array_values(array_diff($permissionIds, $assignedIds)) as $permissionId) {
                $rows[] = [
                    'tenant_id' => $tenantId,
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'metadata' => json_encode([self::SYSTEM_DEFINED => true], JSON_THROW_ON_ERROR),
                    'row_version' => 1,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows !== []) {
                DB::table('role_permissions')->insert($rows);
            }

            return [
                'role_id' => $roleId,
                'permission_count' => count($permissionIds),
            ];
        }, 3);
    }

    public function catalogueIsReady(int $tenantId, bool $lockForUpdate = false): bool
    {
        $expectedNames = array_keys($this->permissionDefinitions->all());
        sort($expectedNames);

        $query = PermissionModel::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_name', self::GUARD_NAME)
            ->whereNull('deleted_at');
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $catalogueNames = $query->pluck('name')
            ->map(static fn (mixed $name): string => (string) $name)
            ->sort()
            ->values()
            ->all();

        return $catalogueNames === $expectedNames;
    }

    public function protectedSuperAdminRoleId(int $tenantId, bool $lockForUpdate = false): ?int
    {
        $query = RoleModel::query()
            ->where('tenant_id', $tenantId)
            ->where('name', UserPermission::SUPER_ADMIN_ROLE)
            ->where('guard_name', self::GUARD_NAME)
            ->whereNull('deleted_at');
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
            ->where('name', UserPermission::SUPER_ADMIN_ROLE)
            ->where('guard_name', self::GUARD_NAME)
            ->whereNull('deleted_at');
        if ($lockForUpdate) {
            $roleQuery->lockForUpdate();
        }
        $role = $roleQuery->first(['id']);
        if (! $role instanceof RoleModel) {
            return false;
        }

        $assignedQuery = DB::table('role_permissions')
            ->join('permissions', function ($join): void {
                $join->on('permissions.id', '=', 'role_permissions.permission_id')
                    ->on('permissions.tenant_id', '=', 'role_permissions.tenant_id');
            })
            ->where('role_permissions.tenant_id', $tenantId)
            ->where('role_permissions.role_id', (int) $role->getKey())
            ->whereNull('permissions.deleted_at')
            ->orderBy('permissions.name');
        if ($lockForUpdate) {
            $assignedQuery->lockForUpdate();
        }

        $assignedNames = $assignedQuery->pluck('permissions.name')
            ->map(static fn (mixed $name): string => (string) $name)
            ->all();

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
            ->where('guard_name', self::GUARD_NAME)
            ->whereNull('deleted_at')
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

        $activeUserExists = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('id', $userId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->when($lockForUpdate, static fn ($query) => $query->lockForUpdate())
            ->exists();
        if (! $activeUserExists) {
            return false;
        }

        $roleAssigned = DB::table('user_roles')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('role_id', $superAdminRoleId)
            ->when($lockForUpdate, static fn ($query) => $query->lockForUpdate())
            ->exists();

        $rootAssigned = DB::table('user_organization_units')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('organization_unit_id', $rootOrganizationUnitId)
            ->where('status', 'active')
            ->when($lockForUpdate, static fn ($query) => $query->lockForUpdate())
            ->exists();

        return $roleAssigned && $rootAssigned;
    }
}
