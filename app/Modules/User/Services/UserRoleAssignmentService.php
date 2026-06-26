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
use Modules\User\Constants\UserStatus;
use Modules\User\Constants\UserSystemRole;
use Modules\User\Contracts\TenantUserAccessRevokerInterface;
use Modules\User\Models\RoleModel;
use Modules\User\Models\UserModel;
use Modules\User\Models\UserRoleModel;
use Modules\User\Services\Audit\UserAuditService;
use RuntimeException;
use Throwable;

final class UserRoleAssignmentService extends AbstractUserCrudService
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

    /** @param list<int> $roleIds */
    public function sync(int|string $userId, int $expectedVersion, array $roleIds): Result
    {
        try {
            if (! $this->authorization->canCurrent(UserPermission::USERS_ASSIGN_ROLES)) {
                throw new AuthorizationException('You are not allowed to assign user roles.');
            }
            $tenantId = $this->requireTenantId();
            $user = DB::transaction(function () use ($tenantId, $userId, $expectedVersion, $roleIds): UserModel {
                $this->tenantLock->lock($tenantId);
                $user = UserModel::query()->where('tenant_id', $tenantId)->whereKey($userId)->lockForUpdate()->first();
                if (! $user instanceof UserModel) {
                    throw new RuntimeException('User not found.');
                }
                $this->assertVersion($user, $expectedVersion);
                $normalized = $this->normalizeIds($roleIds);
                $roles = $this->lockedRoles($tenantId, $normalized);
                $before = $this->assignedRoleIds($tenantId, (int) $user->getKey(), true);
                $removesSuperAdmin = $this->containsSuperAdmin($tenantId, $before)
                    && ! $roles->contains(static fn (RoleModel $role): bool => $role->getAttribute('system_key') === UserSystemRole::SUPER_ADMIN);
                if ($removesSuperAdmin) {
                    $this->assertAnotherActiveSuperAdmin($tenantId, (int) $user->getKey());
                }

                $actorId = $this->actor->currentUserId();
                $assignments = UserRoleModel::query()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $user->getKey())
                    ->orderBy('role_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(static fn (UserRoleModel $assignment): int => (int) $assignment->getAttribute('role_id'));

                foreach ($assignments as $assignedRoleId => $assignment) {
                    if (! in_array((int) $assignedRoleId, $normalized, true)) {
                        $assignment->delete();
                    }
                }
                foreach ($normalized as $roleId) {
                    $existing = $assignments->get($roleId);
                    if ($existing instanceof UserRoleModel) {
                        $existing->forceFill([
                            'row_version' => (int) $existing->getAttribute('row_version') + 1,
                            'updated_by_user_id' => $actorId,
                        ])->save();
                        continue;
                    }
                    UserRoleModel::query()->create([
                        'tenant_id' => $tenantId,
                        'user_id' => $user->getKey(),
                        'role_id' => $roleId,
                        'row_version' => 1,
                        'created_by_user_id' => $actorId,
                        'updated_by_user_id' => $actorId,
                    ]);
                }
                $user->forceFill([
                    'row_version' => $expectedVersion + 1,
                    'updated_by_user_id' => $actorId,
                ])->save();
                $after = $this->assignedRoleIds($tenantId, (int) $user->getKey(), false);
                $this->accessRevoker->revokeSessionsForUser($tenantId, (int) $user->getKey(), 'User roles changed.');
                $this->access->forgetForUserTenant((int) $user->getKey(), $tenantId);
                $this->audit->record('roles.synced', 'user', $user, ['role_ids' => $before], ['role_ids' => $after]);
                return $user;
            }, 3);

            return Result::success(new DataRecord($user->fresh()?->attributesToArray() ?? $user->attributesToArray()));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /** @param list<int> $roleIds */
    public function applyInitialAccess(UserModel $user, array $roleIds): void
    {
        $tenantId = (int) $user->getAttribute('tenant_id');
        $normalized = $this->normalizeIds($roleIds);
        $this->lockedRoles($tenantId, $normalized);
        $actorId = $this->actor->currentUserId();
        foreach ($normalized as $roleId) {
            UserRoleModel::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $user->getKey(),
                'role_id' => $roleId,
                'row_version' => 1,
                'created_by_user_id' => $actorId,
                'updated_by_user_id' => $actorId,
            ]);
        }
    }

    /** @return list<int> */
    public function assignedRoleIds(int $tenantId, int $userId, bool $lock): array
    {
        $query = UserRoleModel::query()->where('tenant_id', $tenantId)->where('user_id', $userId);
        if ($lock) {
            $query->lockForUpdate();
        }
        return $query->orderBy('role_id')->pluck('role_id')->map(static fn (mixed $id): int => (int) $id)->all();
    }

    private function lockedRoles(int $tenantId, array $roleIds): \Illuminate\Support\Collection
    {
        if ($roleIds === []) {
            return collect();
        }
        $roles = RoleModel::query()->where('tenant_id', $tenantId)->whereIn('id', $roleIds)
            ->where('guard_name', UserGuard::TENANT_API)->orderBy('id')->lockForUpdate()->get();
        if ($roles->count() !== count($roleIds)) {
            throw new RuntimeException('One or more selected roles are unavailable in the current tenant.');
        }
        return $roles;
    }

    /** @param list<int> $roleIds */
    private function containsSuperAdmin(int $tenantId, array $roleIds): bool
    {
        return $roleIds !== [] && RoleModel::query()->where('tenant_id', $tenantId)
            ->whereIn('id', $roleIds)->where('system_key', UserSystemRole::SUPER_ADMIN)->exists();
    }

    private function assertAnotherActiveSuperAdmin(int $tenantId, int $excludingUserId): void
    {
        $exists = DB::table('users')
            ->join('user_roles', function ($join): void {
                $join->on('user_roles.user_id', '=', 'users.id')->on('user_roles.tenant_id', '=', 'users.tenant_id');
            })
            ->join('roles', function ($join): void {
                $join->on('roles.id', '=', 'user_roles.role_id')->on('roles.tenant_id', '=', 'user_roles.tenant_id');
            })
            ->where('users.tenant_id', $tenantId)->where('users.id', '!=', $excludingUserId)
            ->where('users.status', UserStatus::ACTIVE)->whereNotNull('users.credentials_ready_at')
            ->whereNull('users.deleted_at')->whereNull('roles.deleted_at')
            ->where('roles.system_key', UserSystemRole::SUPER_ADMIN)
            ->select('users.id')->lockForUpdate()->first() !== null;
        if (! $exists) {
            throw new RuntimeException('The last active Super Admin role assignment cannot be removed.');
        }
    }

    private function assertVersion(UserModel $user, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $user->getAttribute('row_version') !== $expectedVersion) {
            throw new RuntimeException('The user changed after it was loaded. Refresh and try again.');
        }
    }

    /** @param list<int|numeric-string> $ids @return list<int> */
    private function normalizeIds(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            if (! is_numeric($id) || (int) $id < 1) {
                throw new RuntimeException('Role identifiers must be positive integers.');
            }
            $result[(int) $id] = (int) $id;
        }
        ksort($result);
        return array_values($result);
    }

    private function requireTenantId(): int
    {
        $id = $this->tenant->currentTenantId();
        if ($id === null) {
            throw new RuntimeException('A tenant context is required.');
        }
        return $id;
    }

}
