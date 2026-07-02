<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantAggregateLockInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Result;
use Modules\Core\Tenancy\TenantPlanLimit;
use Modules\Core\Contracts\TenantEntitlementReaderInterface;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Modules\User\Constants\UserPermission;
use Modules\User\Constants\UserStatus;
use Modules\User\Constants\UserSystemRole;
use Modules\User\Contracts\TenantUserAccessRevokerInterface;
use Modules\User\Contracts\TenantUserCredentialProvisionerInterface;
use Modules\User\Models\UserDeviceModel;
use Modules\User\Models\UserModel;
use Modules\User\Models\UserOrganizationUnitModel;
use Modules\User\Models\UserPermissionModel;
use Modules\User\Models\UserRoleModel;
use Modules\User\Services\Audit\UserAuditService;
use RuntimeException;
use Throwable;

final class UserAccountService extends AbstractUserCrudService
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $tenant,
        private readonly CurrentUserContextAccessorInterface $actor,
        private readonly UserAuthorizationService $authorization,
        private readonly TenantEntitlementReaderInterface $entitlements,
        private readonly UserRoleAssignmentService $roles,
        private readonly UserOrganizationAccessService $organizationAccess,
        private readonly TenantUserCredentialProvisionerInterface $credentials,
        private readonly TenantUserAccessRevokerInterface $accessRevoker,
        private readonly UserAuditService $audit,
        private readonly ClockInterface $clock,
        private readonly TenantAggregateLockInterface $tenantLock,
    ) {}

    public function create(array $payload): Result
    {
        try {
            $this->assertPermission(UserPermission::USERS_CREATE, 'You are not allowed to create users.');
            $roleIds = $this->normalizeIds($payload['role_ids'] ?? []);
            $organizationUnitIds = $this->normalizeIds($payload['organization_unit_ids'] ?? []);
            $defaultOrganizationUnitId = is_numeric($payload['default_organization_unit_id'] ?? null)
                ? (int) $payload['default_organization_unit_id']
                : 0;
            if ($roleIds !== []) {
                $this->assertPermission(UserPermission::USERS_ASSIGN_ROLES, 'You are not allowed to assign roles during onboarding.');
            }
            $this->assertPermission(
                UserPermission::USERS_MANAGE_ORGANIZATION_ACCESS,
                'You are not allowed to assign organization access during onboarding.',
            );

            $tenantId = $this->requireTenantId();
            $created = DB::transaction(function () use (
                $tenantId, $payload, $roleIds, $organizationUnitIds, $defaultOrganizationUnitId,
            ): UserModel {
                $this->tenantLock->lock($tenantId);
                $limit = $this->entitlements->limit($tenantId, TenantPlanLimit::USERS);
                if ($limit !== null && UserModel::query()->where('tenant_id', $tenantId)->count() >= $limit) {
                    throw new RuntimeException('The tenant plan user limit has been reached.');
                }

                $email = strtolower(trim((string) ($payload['email'] ?? '')));
                $username = $this->nullableLowerString($payload['username'] ?? null);
                $this->assertUniqueIdentity($tenantId, $email, $username, null);
                $actorId = $this->actor->currentUserId();
                $now = $this->clock->now();
                $user = UserModel::query()->create([
                    'tenant_id' => $tenantId,
                    'row_version' => 1,
                    'first_name' => trim((string) ($payload['first_name'] ?? '')),
                    'last_name' => $this->nullableString($payload['last_name'] ?? null),
                    'username' => $username,
                    'email' => $email,
                    'email_verified_at' => $now,
                    'status' => UserStatus::ACTIVE,
                    'phone' => $this->nullableString($payload['phone'] ?? null),
                    'credentials_ready_at' => $now,
                    'invited_at' => null,
                    'activated_at' => $now,
                    'created_by_user_id' => $actorId,
                    'updated_by_user_id' => $actorId,
                ]);
                $this->roles->applyInitialAccess($user, $roleIds);
                $this->organizationAccess->applyInitialAccess($user, $organizationUnitIds, $defaultOrganizationUnitId);
                $this->credentials->provisionTenantUser(
                    $tenantId,
                    (int) $user->getKey(),
                    $email,
                    (string) ($payload['password'] ?? ''),
                );
                $this->audit->record('account.created', 'user', $user, null, $this->snapshot($user));
                return $user;
            }, 3);

            /** @var UserModel $user */
            $user = $created;
            return Result::success(new DataRecord($this->snapshot($user->fresh() ?? $user)));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /** @return array{minimum_length:int,mixed_case:bool,numbers:bool,symbols:bool} */
    public function passwordRequirements(): array
    {
        return $this->credentials->passwordRequirements();
    }

    public function updateProfile(int|string $userId, int $expectedVersion, array $payload): Result
    {
        try {
            $this->assertPermission(UserPermission::USERS_UPDATE, 'You are not allowed to update user profiles.');
            $tenantId = $this->requireTenantId();
            $user = DB::transaction(function () use ($tenantId, $userId, $expectedVersion, $payload): UserModel {
                $this->tenantLock->lock($tenantId);
                $user = $this->findLocked($tenantId, $userId);
                $this->assertVersion($user, $expectedVersion);
                $before = $this->snapshot($user);
                $username = array_key_exists('username', $payload)
                    ? $this->nullableLowerString($payload['username'])
                    : $user->getAttribute('username');
                $this->assertUniqueIdentity($tenantId, (string) $user->getAttribute('email'), $username, (int) $user->getKey());
                $actorId = $this->actor->currentUserId();
                $user->forceFill([
                    'first_name' => array_key_exists('first_name', $payload)
                        ? trim((string) $payload['first_name']) : $user->getAttribute('first_name'),
                    'last_name' => array_key_exists('last_name', $payload)
                        ? $this->nullableString($payload['last_name']) : $user->getAttribute('last_name'),
                    'username' => $username,
                    'phone' => array_key_exists('phone', $payload)
                        ? $this->nullableString($payload['phone']) : $user->getAttribute('phone'),
                    'row_version' => $expectedVersion + 1,
                    'updated_by_user_id' => $actorId,
                ])->save();
                $this->audit->record('profile.updated', 'user', $user, $before, $this->snapshot($user));
                return $user;
            }, 3);
            return Result::success(new DataRecord($this->snapshot($user->fresh() ?? $user)));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function changeStatus(int|string $userId, int $expectedVersion, string $status, string $reason): Result
    {
        try {
            $status = strtolower(trim($status));
            if (! in_array($status, UserStatus::mutableValues(), true)) {
                throw new RuntimeException('Account status is invalid.');
            }
            $permission = $status === UserStatus::ACTIVE ? UserPermission::USERS_ACTIVATE : UserPermission::USERS_DEACTIVATE;
            $this->assertPermission($permission, 'You are not allowed to change this account status.');
            $tenantId = $this->requireTenantId();
            $user = DB::transaction(function () use ($tenantId, $userId, $expectedVersion, $status, $reason): UserModel {
                $this->tenantLock->lock($tenantId);
                $user = $this->findLocked($tenantId, $userId);
                $this->assertVersion($user, $expectedVersion);
                if ((string) $user->getAttribute('status') === $status) {
                    return $user;
                }
                if ($status === UserStatus::ACTIVE) {
                    if ($user->getAttribute('credentials_ready_at') === null) {
                        throw new RuntimeException('The user account must have credentials before activation.');
                    }
                    if (! UserOrganizationUnitModel::query()->where('tenant_id', $tenantId)
                        ->where('user_id', $user->getKey())->where('status', UserOrganizationUnitStatus::ACTIVE)->where('is_default', true)->exists()) {
                        throw new RuntimeException('An active default organization unit is required before activation.');
                    }
                } else {
                    if ($this->actor->currentUserId() === (int) $user->getKey()) {
                        throw new RuntimeException('You cannot deactivate or suspend your own account.');
                    }
                    $this->assertNotLastActiveSuperAdmin($tenantId, (int) $user->getKey());
                    $this->accessRevoker->revokeSessionsForUser($tenantId, (int) $user->getKey(), trim($reason));
                }
                $before = $this->snapshot($user);
                $now = $this->clock->now();
                $user->forceFill([
                    'status' => $status,
                    'activated_at' => $status === UserStatus::ACTIVE ? ($user->getAttribute('activated_at') ?? $now) : $user->getAttribute('activated_at'),
                    'deactivated_at' => $status === UserStatus::INACTIVE ? $now : null,
                    'suspended_at' => $status === UserStatus::SUSPENDED ? $now : null,
                    'row_version' => $expectedVersion + 1,
                    'updated_by_user_id' => $this->actor->currentUserId(),
                ])->save();
                $this->audit->record('status.changed', 'user', $user, $before, $this->snapshot($user), $reason);
                return $user;
            }, 3);
            return Result::success(new DataRecord($this->snapshot($user->fresh() ?? $user)));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function delete(int|string $userId, int $expectedVersion, string $reason): Result
    {
        try {
            $this->assertPermission(UserPermission::USERS_DELETE, 'You are not allowed to archive users.');
            $tenantId = $this->requireTenantId();
            DB::transaction(function () use ($tenantId, $userId, $expectedVersion, $reason): void {
                $this->tenantLock->lock($tenantId);
                $user = $this->findLocked($tenantId, $userId);
                $this->assertVersion($user, $expectedVersion);
                if ($this->actor->currentUserId() === (int) $user->getKey()) {
                    throw new RuntimeException('You cannot archive your own account.');
                }
                $this->assertNotLastActiveSuperAdmin($tenantId, (int) $user->getKey());
                $before = $this->snapshot($user);
                $this->accessRevoker->revokeAllForUser($tenantId, (int) $user->getKey(), trim($reason));
                UserRoleModel::query()->where('tenant_id', $tenantId)->where('user_id', $user->getKey())->delete();
                UserPermissionModel::query()->where('tenant_id', $tenantId)->where('user_id', $user->getKey())->delete();
                UserOrganizationUnitModel::query()->where('tenant_id', $tenantId)->where('user_id', $user->getKey())->delete();
                UserDeviceModel::query()->where('tenant_id', $tenantId)->where('user_id', $user->getKey())
                    ->whereNull('revoked_at')->update([
                        'revoked_at' => $this->clock->now(),
                        'revoked_by_user_id' => $this->actor->currentUserId(),
                        'row_version' => DB::raw('row_version + 1'),
                        'updated_at' => $this->clock->now(),
                    ]);
                $user->forceFill([
                    'status' => UserStatus::INACTIVE,
                    'deleted_by_user_id' => $this->actor->currentUserId(),
                    'deactivated_at' => $this->clock->now(),
                    'row_version' => $expectedVersion + 1,
                ])->save();
                $user->delete();
                $this->audit->record('account.archived', 'user', $user, $before, null, $reason);
            }, 3);
            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    private function assertNotLastActiveSuperAdmin(int $tenantId, int $userId): void
    {
        $isSuperAdmin = DB::table('user_roles')->join('roles', function ($join): void {
            $join->on('roles.id', '=', 'user_roles.role_id')->on('roles.tenant_id', '=', 'user_roles.tenant_id');
        })->where('user_roles.tenant_id', $tenantId)->where('user_roles.user_id', $userId)
            ->where('roles.system_key', UserSystemRole::SUPER_ADMIN)->whereNull('roles.deleted_at')->exists();
        if (! $isSuperAdmin) {
            return;
        }
        $another = DB::table('users')
            ->join('user_roles', function ($join): void {
                $join->on('user_roles.user_id', '=', 'users.id')->on('user_roles.tenant_id', '=', 'users.tenant_id');
            })
            ->join('roles', function ($join): void {
                $join->on('roles.id', '=', 'user_roles.role_id')->on('roles.tenant_id', '=', 'user_roles.tenant_id');
            })
            ->where('users.tenant_id', $tenantId)->where('users.id', '!=', $userId)
            ->where('users.status', UserStatus::ACTIVE)->whereNotNull('users.credentials_ready_at')
            ->whereNull('users.deleted_at')->whereNull('roles.deleted_at')
            ->where('roles.system_key', UserSystemRole::SUPER_ADMIN)
            ->select('users.id')->lockForUpdate()->first() !== null;
        if (! $another) {
            throw new RuntimeException('The last active Super Admin account cannot be deactivated or archived.');
        }
    }

    private function assertUniqueIdentity(int $tenantId, string $email, ?string $username, ?int $excludingId): void
    {
        $emailQuery = UserModel::withTrashed()->where('tenant_id', $tenantId)->where('email', $email);
        if ($excludingId !== null) {
            $emailQuery->where($emailQuery->getModel()->getKeyName(), '!=', $excludingId);
        }
        if ($email === '' || $emailQuery->exists()) {
            throw new RuntimeException('The email address is already assigned to a current or archived user.');
        }
        if ($username === null) {
            return;
        }
        $usernameQuery = UserModel::withTrashed()->where('tenant_id', $tenantId)->where('username', $username);
        if ($excludingId !== null) {
            $usernameQuery->where($usernameQuery->getModel()->getKeyName(), '!=', $excludingId);
        }
        if ($usernameQuery->exists()) {
            throw new RuntimeException('The username is already assigned to a current or archived user.');
        }
    }

    private function findLocked(int $tenantId, int|string $userId): UserModel
    {
        $user = UserModel::query()->where('tenant_id', $tenantId)->whereKey($userId)->lockForUpdate()->first();
        if (! $user instanceof UserModel) {
            throw new RuntimeException('User not found.');
        }
        return $user;
    }

    private function assertVersion(UserModel $user, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $user->getAttribute('row_version') !== $expectedVersion) {
            throw new RuntimeException('The user changed after it was loaded. Refresh and try again.');
        }
    }

    private function assertPermission(string $permission, string $message): void
    {
        if (! $this->authorization->canCurrent($permission)) {
            throw new AuthorizationException($message);
        }
    }

    private function requireTenantId(): int
    {
        $id = $this->tenant->currentTenantId();
        if ($id === null) {
            throw new RuntimeException('A tenant context is required.');
        }
        return $id;
    }


    private function normalizeIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }
        $result = [];
        foreach ($ids as $id) {
            if (! is_numeric($id) || (int) $id < 1) {
                throw new RuntimeException('Identifiers must be positive integers.');
            }
            $result[(int) $id] = (int) $id;
        }
        ksort($result);
        return array_values($result);
    }

    /** @return array<string,mixed> */
    private function snapshot(UserModel $user): array
    {
        return [
            'id' => (int) $user->getKey(),
            'row_version' => (int) $user->getAttribute('row_version'),
            'first_name' => (string) $user->getAttribute('first_name'),
            'last_name' => $user->getAttribute('last_name'),
            'username' => $user->getAttribute('username'),
            'email' => (string) $user->getAttribute('email'),
            'phone' => $user->getAttribute('phone'),
            'status' => (string) $user->getAttribute('status'),
            'credentials_ready' => $user->getAttribute('credentials_ready_at') !== null,
            'invited_at' => $user->getAttribute('invited_at')?->format(DATE_ATOM),
            'activated_at' => $user->getAttribute('activated_at')?->format(DATE_ATOM),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return $value === '' ? null : $value;
    }

    private function nullableLowerString(mixed $value): ?string
    {
        $value = $this->nullableString($value);
        return $value === null ? null : strtolower($value);
    }
}
