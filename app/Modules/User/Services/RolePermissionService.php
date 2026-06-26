<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\TenantAggregateLockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserGuard;
use Modules\User\Constants\UserPermission;
use Modules\User\Contracts\TenantUserAccessRevokerInterface;
use Modules\User\Models\PermissionModel;
use Modules\User\Models\RoleModel;
use Modules\User\Models\RolePermissionModel;
use Modules\User\Models\UserRoleModel;
use Modules\User\Services\Audit\UserAuditService;
use RuntimeException;
use Throwable;

final class RolePermissionService extends AbstractUserCrudService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $tenant,
        private readonly CurrentUserContextAccessorInterface $actor,
        private readonly UserAuthorizationService $authorization,
        private readonly TenantUserAccessRevokerInterface $accessRevoker,
        private readonly UserAccessResolver $access,
        private readonly UserAuditService $audit,
        private readonly TenantAggregateLockInterface $tenantLock,
    ) {}

    /** @param list<int> $permissionIds */
    public function sync(int|string $roleId, int $expectedVersion, array $permissionIds): Result
    {
        try {
            if (! $this->authorization->canCurrent(UserPermission::ROLES_ASSIGN_PERMISSIONS)) {
                throw new AuthorizationException('You are not allowed to assign role permissions.');
            }

            $tenantId = $this->tenantId();
            $role = DB::transaction(function () use ($tenantId, $roleId, $expectedVersion, $permissionIds): RoleModel {
                $this->tenantLock->lock($tenantId);
                $role = RoleModel::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($roleId)
                    ->lockForUpdate()
                    ->first();

                if (! $role instanceof RoleModel) {
                    throw new RuntimeException('Role not found.');
                }
                $this->assertVersion($role, $expectedVersion);
                if ((bool) $role->getAttribute('is_system')) {
                    throw new RuntimeException('System role permissions are synchronized from the authoritative module catalogue.');
                }

                $ids = $this->normalizeIds($permissionIds);
                $this->lockPermissions($tenantId, $ids);
                $assignments = RolePermissionModel::query()
                    ->where('tenant_id', $tenantId)
                    ->where('role_id', $role->getKey())
                    ->orderBy('permission_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(static fn (RolePermissionModel $assignment): int => (int) $assignment->getAttribute('permission_id'));
                $before = $assignments->keys()->map(static fn (mixed $id): int => (int) $id)->values()->all();
                $actorId = $this->actor->currentUserId();

                foreach ($assignments as $permissionId => $assignment) {
                    if (! in_array((int) $permissionId, $ids, true)) {
                        $assignment->delete();
                    }
                }

                foreach ($ids as $permissionId) {
                    $existing = $assignments->get($permissionId);
                    if ($existing instanceof RolePermissionModel) {
                        $existing->forceFill([
                            'row_version' => (int) $existing->getAttribute('row_version') + 1,
                            'updated_by_user_id' => $actorId,
                        ])->save();
                        continue;
                    }

                    RolePermissionModel::query()->create([
                        'tenant_id' => $tenantId,
                        'role_id' => $role->getKey(),
                        'permission_id' => $permissionId,
                        'row_version' => 1,
                        'created_by_user_id' => $actorId,
                        'updated_by_user_id' => $actorId,
                    ]);
                }

                $role->forceFill([
                    'row_version' => $expectedVersion + 1,
                    'updated_by_user_id' => $actorId,
                ])->save();

                $userIds = UserRoleModel::query()
                    ->where('tenant_id', $tenantId)
                    ->where('role_id', $role->getKey())
                    ->lockForUpdate()
                    ->pluck('user_id')
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->all();
                foreach ($userIds as $userId) {
                    $this->accessRevoker->revokeSessionsForUser($tenantId, $userId, 'Role permissions changed.');
                    $this->access->forgetForUserTenant($userId, $tenantId);
                }

                $this->access->forgetForRoleTenant((int) $role->getKey(), $tenantId);
                $this->audit->record(
                    'role.permissions_synced',
                    'role',
                    $role,
                    ['permission_ids' => $before],
                    ['permission_ids' => $ids],
                );

                return $role;
            }, 3);

            return Result::success(new DataRecord([
                'id' => (int) $role->getKey(),
                'row_version' => (int) $role->getAttribute('row_version'),
                'permission_ids' => $this->assignedPermissionIds($tenantId, (int) $role->getKey()),
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /** @param list<int> $permissionIds */
    private function lockPermissions(int $tenantId, array $permissionIds): void
    {
        if ($permissionIds === []) {
            return;
        }

        $count = PermissionModel::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_name', UserGuard::TENANT_API)
            ->where('is_active', true)
            ->whereIn('id', $permissionIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id'])
            ->count();

        if ($count !== count($permissionIds)) {
            throw new RuntimeException('One or more permissions are invalid, inactive, or use another guard.');
        }
    }

    /** @return list<int> */
    private function assignedPermissionIds(int $tenantId, int $roleId): array
    {
        return RolePermissionModel::query()
            ->where('tenant_id', $tenantId)
            ->where('role_id', $roleId)
            ->orderBy('permission_id')
            ->pluck('permission_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /** @param list<int|numeric-string> $ids @return list<int> */
    private function normalizeIds(array $ids): array
    {
        $normalized = [];
        foreach ($ids as $id) {
            if (! is_numeric($id) || (int) $id < 1) {
                throw new RuntimeException('Permission identifiers must be positive integers.');
            }
            $normalized[(int) $id] = (int) $id;
        }
        ksort($normalized);

        return array_values($normalized);
    }

    private function assertVersion(RoleModel $role, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $role->getAttribute('row_version') !== $expectedVersion) {
            throw new RuntimeException('The role changed after it was loaded. Refresh and try again.');
        }
    }

    private function tenantId(): int
    {
        $tenantId = $this->tenant->currentTenantId();
        if ($tenantId === null) {
            throw new RuntimeException('A tenant context is required.');
        }

        return $tenantId;
    }

}
