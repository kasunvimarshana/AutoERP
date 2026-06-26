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
use Modules\User\Models\UserModel;
use Modules\User\Models\UserPermissionModel;
use Modules\User\Services\Audit\UserAuditService;
use RuntimeException;
use Throwable;

final class UserPermissionAssignmentService extends AbstractUserCrudService
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
    public function sync(int|string $userId, int $expectedVersion, array $permissionIds): Result
    {
        try {
            if (! $this->authorization->canCurrent(UserPermission::USERS_ASSIGN_PERMISSIONS)) {
                throw new AuthorizationException('You are not allowed to assign direct user permissions.');
            }

            $tenantId = $this->requireTenantId();
            $user = DB::transaction(function () use ($tenantId, $userId, $expectedVersion, $permissionIds): UserModel {
                $this->tenantLock->lock($tenantId);
                $user = UserModel::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($userId)
                    ->lockForUpdate()
                    ->first();

                if (! $user instanceof UserModel) {
                    throw new RuntimeException('User not found.');
                }
                $this->assertVersion($user, $expectedVersion);

                $normalized = $this->normalizeIds($permissionIds);
                $this->lockPermissions($tenantId, $normalized);
                $assignments = UserPermissionModel::query()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $user->getKey())
                    ->orderBy('permission_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(static fn (UserPermissionModel $assignment): int => (int) $assignment->getAttribute('permission_id'));
                $before = $assignments->keys()->map(static fn (mixed $id): int => (int) $id)->values()->all();
                $actorId = $this->actor->currentUserId();

                foreach ($assignments as $permissionId => $assignment) {
                    if (! in_array((int) $permissionId, $normalized, true)) {
                        $assignment->delete();
                    }
                }

                foreach ($normalized as $permissionId) {
                    $existing = $assignments->get($permissionId);
                    if ($existing instanceof UserPermissionModel) {
                        $existing->forceFill([
                            'row_version' => (int) $existing->getAttribute('row_version') + 1,
                            'updated_by_user_id' => $actorId,
                        ])->save();
                        continue;
                    }

                    UserPermissionModel::query()->create([
                        'tenant_id' => $tenantId,
                        'user_id' => $user->getKey(),
                        'permission_id' => $permissionId,
                        'row_version' => 1,
                        'created_by_user_id' => $actorId,
                        'updated_by_user_id' => $actorId,
                    ]);
                }

                $user->forceFill([
                    'row_version' => $expectedVersion + 1,
                    'updated_by_user_id' => $actorId,
                ])->save();

                $this->accessRevoker->revokeSessionsForUser(
                    $tenantId,
                    (int) $user->getKey(),
                    'Direct user permissions changed.',
                );
                $this->access->forgetForUserTenant((int) $user->getKey(), $tenantId);
                $this->audit->record(
                    'permissions.synced',
                    'user',
                    $user,
                    ['permission_ids' => $before],
                    ['permission_ids' => $normalized],
                );

                return $user;
            }, 3);

            return Result::success(new DataRecord([
                'id' => (int) $user->getKey(),
                'row_version' => (int) $user->getAttribute('row_version'),
                'permission_ids' => $this->assignedPermissionIds($tenantId, (int) $user->getKey()),
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
            throw new RuntimeException('One or more selected permissions are unavailable in the current tenant.');
        }
    }

    /** @return list<int> */
    private function assignedPermissionIds(int $tenantId, int $userId): array
    {
        return UserPermissionModel::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
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

    private function assertVersion(UserModel $user, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $user->getAttribute('row_version') !== $expectedVersion) {
            throw new RuntimeException('The user changed after it was loaded. Refresh and try again.');
        }
    }

    private function requireTenantId(): int
    {
        $tenantId = $this->tenant->currentTenantId();
        if ($tenantId === null) {
            throw new RuntimeException('A tenant context is required.');
        }

        return $tenantId;
    }

}
