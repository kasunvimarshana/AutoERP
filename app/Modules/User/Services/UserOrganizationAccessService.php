<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\TenantAggregateLockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\OrganizationUnitAuthScopeRevokerInterface;
use Modules\Core\Contracts\OrganizationUnitDirectoryInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Modules\User\Constants\UserPermission;
use Modules\User\Contracts\TenantUserAccessRevokerInterface;
use Modules\User\Models\UserModel;
use Modules\User\Models\UserOrganizationUnitModel;
use Modules\User\Services\Audit\UserAuditService;
use RuntimeException;
use Throwable;

final class UserOrganizationAccessService extends AbstractUserCrudService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $tenant,
        private readonly CurrentUserContextAccessorInterface $actor,
        private readonly UserAuthorizationService $authorization,
        private readonly OrganizationUnitAuthScopeRevokerInterface $organizationUnitAuthScopes,
        private readonly OrganizationUnitDirectoryInterface $organizationUnits,
        private readonly TenantUserAccessRevokerInterface $accessRevoker,
        private readonly UserAuditService $audit,
        private readonly TenantAggregateLockInterface $tenantLock,
    ) {}

    /** @param list<int> $organizationUnitIds */
    public function sync(int|string $userId, int $expectedVersion, array $organizationUnitIds, int $defaultOrganizationUnitId): Result
    {
        try {
            if (! $this->authorization->canCurrent(UserPermission::USERS_MANAGE_ORGANIZATION_ACCESS)) {
                throw new AuthorizationException('You are not allowed to manage organization access.');
            }
            $tenantId = $this->requireTenantId();
            $user = DB::transaction(function () use (
                $tenantId, $userId, $expectedVersion, $organizationUnitIds, $defaultOrganizationUnitId,
            ): UserModel {
                $this->tenantLock->lock($tenantId);
                $user = UserModel::query()->where('tenant_id', $tenantId)->whereKey($userId)->lockForUpdate()->first();
                if (! $user instanceof UserModel) {
                    throw new RuntimeException('User not found.');
                }
                $this->assertVersion($user, $expectedVersion);
                $ids = $this->normalizeIds($organizationUnitIds);
                $this->assertDefault($ids, $defaultOrganizationUnitId);
                $this->lockActiveUnits($tenantId, $ids);
                $before = $this->assignmentSnapshot($tenantId, (int) $user->getKey(), true);
                $removed = array_values(array_diff(array_column($before, 'organization_unit_id'), $ids));
                $actorId = $this->actor->currentUserId();

                UserOrganizationUnitModel::query()->where('tenant_id', $tenantId)->where('user_id', $user->getKey())
                    ->whereNotIn('organization_unit_id', $ids)->delete();
                foreach ($ids as $organizationUnitId) {
                    $isDefault = $organizationUnitId === $defaultOrganizationUnitId;
                    $assignment = UserOrganizationUnitModel::query()
                        ->where('tenant_id', $tenantId)
                        ->where('user_id', $user->getKey())
                        ->where('organization_unit_id', $organizationUnitId)
                        ->lockForUpdate()
                        ->first();
                    if ($assignment instanceof UserOrganizationUnitModel) {
                        $assignment->forceFill([
                            'status' => UserOrganizationUnitStatus::ACTIVE,
                            'is_default' => $isDefault,
                            'default_marker' => $isDefault ? UserOrganizationUnitStatus::DEFAULT_MARKER : null,
                            'row_version' => (int) $assignment->getAttribute('row_version') + 1,
                            'updated_by_user_id' => $actorId,
                        ])->save();
                    } else {
                        UserOrganizationUnitModel::query()->create([
                            'tenant_id' => $tenantId,
                            'user_id' => $user->getKey(),
                            'organization_unit_id' => $organizationUnitId,
                            'status' => UserOrganizationUnitStatus::ACTIVE,
                            'is_default' => $isDefault,
                            'default_marker' => $isDefault ? UserOrganizationUnitStatus::DEFAULT_MARKER : null,
                            'row_version' => 1,
                            'created_by_user_id' => $actorId,
                            'updated_by_user_id' => $actorId,
                        ]);
                    }
                }
                $user->forceFill(['row_version' => $expectedVersion + 1, 'updated_by_user_id' => $actorId])->save();
                foreach ($removed as $removedId) {
                    $this->organizationUnitAuthScopes->revokeForUserOrganizationUnit(
                        $tenantId,
                        (int) $user->getKey(),
                        (int) $removedId,
                    );
                }
                $this->accessRevoker->revokeSessionsForUser($tenantId, (int) $user->getKey(), 'Organization access changed.');
                $after = $this->assignmentSnapshot($tenantId, (int) $user->getKey(), false);
                $this->audit->record('organization_access.synced', 'user', $user, ['assignments' => $before], ['assignments' => $after]);
                return $user;
            }, 3);
            return Result::success(new DataRecord($user->fresh()?->attributesToArray() ?? $user->attributesToArray()));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /** @param list<int> $organizationUnitIds */
    public function applyInitialAccess(UserModel $user, array $organizationUnitIds, int $defaultOrganizationUnitId): void
    {
        $tenantId = (int) $user->getAttribute('tenant_id');
        $ids = $this->normalizeIds($organizationUnitIds);
        $this->assertDefault($ids, $defaultOrganizationUnitId);
        $this->lockActiveUnits($tenantId, $ids);
        $actorId = $this->actor->currentUserId();
        foreach ($ids as $organizationUnitId) {
            $isDefault = $organizationUnitId === $defaultOrganizationUnitId;
            UserOrganizationUnitModel::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $user->getKey(),
                'organization_unit_id' => $organizationUnitId,
                'status' => UserOrganizationUnitStatus::ACTIVE,
                'is_default' => $isDefault,
                'default_marker' => $isDefault ? UserOrganizationUnitStatus::DEFAULT_MARKER : null,
                'row_version' => 1,
                'created_by_user_id' => $actorId,
                'updated_by_user_id' => $actorId,
            ]);
        }
    }

    /** @return list<array{organization_unit_id:int,is_default:bool}> */
    private function assignmentSnapshot(int $tenantId, int $userId, bool $lock): array
    {
        $query = UserOrganizationUnitModel::query()->where('tenant_id', $tenantId)
            ->where('user_id', $userId)->where('status', UserOrganizationUnitStatus::ACTIVE);
        if ($lock) {
            $query->lockForUpdate();
        }
        return $query->orderBy('organization_unit_id')->get()
            ->map(static fn (UserOrganizationUnitModel $assignment): array => [
                'organization_unit_id' => (int) $assignment->getAttribute('organization_unit_id'),
                'is_default' => (bool) $assignment->getAttribute('is_default'),
            ])->all();
    }

    private function lockActiveUnits(int $tenantId, array $ids): void
    {
        $this->organizationUnits->assertActive($tenantId, $ids, true);
    }

    private function assertDefault(array $ids, int $defaultId): void
    {
        if ($ids === [] || $defaultId < 1 || ! in_array($defaultId, $ids, true)) {
            throw new RuntimeException('Select at least one organization unit and choose its default.');
        }
    }

    private function assertVersion(UserModel $user, int $expected): void
    {
        if ($expected < 1 || (int) $user->getAttribute('row_version') !== $expected) {
            throw new RuntimeException('The user changed after it was loaded. Refresh and try again.');
        }
    }

    private function normalizeIds(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            if (! is_numeric($id) || (int) $id < 1) {
                throw new RuntimeException('Organization-unit identifiers must be positive integers.');
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
