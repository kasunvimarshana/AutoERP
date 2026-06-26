<?php

declare(strict_types=1);

namespace Modules\User\Services\Authentication;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Core\Contracts\OrganizationUnitUserAccessCheckerInterface;
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Constants\UserStatus;
use Modules\User\Contracts\AuthenticationPrincipalProviderInterface;
use Modules\User\Contracts\PlatformOperatorAuthenticationDirectoryInterface;
use Modules\User\Contracts\TenantUserAuthenticationDirectoryInterface;
use Modules\User\Models\PlatformOperatorModel;
use Modules\User\Models\UserModel;
use Modules\User\Repositories\UserRoleRepositoryInterface;
use Modules\User\Services\UserAccessResolver;

final class AuthenticationDirectory implements
    TenantUserAuthenticationDirectoryInterface,
    PlatformOperatorAuthenticationDirectoryInterface,
    AuthenticationPrincipalProviderInterface
{
    public function __construct(
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly UserAccessResolver $accessResolver,
        private readonly OrganizationUnitUserAccessCheckerInterface $organizationUnitAccess,
        private readonly UserRoleRepositoryInterface $roles,
        private readonly PlatformPermissionCheckerInterface $platformPermissions,
    ) {}

    public function findTenantForLogin(int $tenantId, string $identifier): ?array
    {
        return $this->lookupTenantForLogin($tenantId, $identifier);
    }

    public function findPlatformForLogin(string $email): ?array
    {
        return $this->lookupPlatformForLogin($email);
    }

    public function findActiveTenantById(int $tenantId, int $userId): ?array
    {
        $principal = $this->tenantPrincipal($tenantId, $userId);
        return $principal instanceof UserModel ? $this->tenantRecord($principal) : null;
    }

    public function findActivePlatformById(int $operatorId): ?array
    {
        $principal = $this->platformPrincipal($operatorId);
        return $principal instanceof PlatformOperatorModel ? $this->platformRecord($principal) : null;
    }

    public function summariesByIds(array $operatorIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $operatorIds),
            static fn (int $id): bool => $id > 0,
        )));
        if ($ids === []) {
            return [];
        }

        return $this->executionContext->runAsControlPlane(function () use ($ids): array {
            return PlatformOperatorModel::query()->whereIn('id', $ids)->get()
                ->mapWithKeys(fn (PlatformOperatorModel $operator): array => [
                    (int) $operator->getKey() => $this->platformRecord($operator),
                ])->all();
        });
    }

    public function tenantPrincipal(int $tenantId, int $userId): ?Authenticatable
    {
        return $this->executionContext->runForTenant($tenantId, static fn (): ?UserModel => UserModel::query()
            ->whereKey($userId)->where('tenant_id', $tenantId)
            ->where('status', UserStatus::ACTIVE)->whereNotNull('credentials_ready_at')
            ->whereNull('deleted_at')->first());
    }

    public function platformPrincipal(int $operatorId): ?Authenticatable
    {
        return $this->executionContext->runAsControlPlane(static fn (): ?PlatformOperatorModel => PlatformOperatorModel::query()
            ->whereKey($operatorId)->where('status', PlatformOperatorStatus::ACTIVE)
            ->whereNotNull('credentials_ready_at')->first());
    }

    public function defaultOrganizationUnitIds(int $tenantId, int $userId): array
    {
        return $this->accessResolver->defaultOrganizationUnitIds($userId, $tenantId);
    }

    public function canAccessOrganizationUnit(int $tenantId, int $userId, int $organizationUnitId, bool $lockForUpdate = false): bool
    {
        return $this->organizationUnitAccess->canAccessOrganizationUnit(
            $userId,
            $tenantId,
            $organizationUnitId,
            $lockForUpdate,
        );
    }

    public function roleNames(int $tenantId, int $userId): array
    {
        return array_values(array_unique(array_map(
            static fn (array $role): string => (string) $role['name'],
            $this->roles->listRoleSummariesForTenantUser($tenantId, $userId),
        )));
    }

    public function permissionNames(int $principalId, ?int $userId = null): array
    {
        if ($userId === null) {
            return $this->platformPermissions->permissions($principalId);
        }

        return $this->accessResolver->effectivePermissionNames($userId, $principalId);
    }

    private function lookupTenantForLogin(int $tenantId, string $identifier): ?array
    {
        $identifier = strtolower(trim($identifier));
        if ($tenantId < 1 || $identifier === '') {
            return null;
        }

        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $identifier): ?array {
            $user = UserModel::query()->where('tenant_id', $tenantId)
                ->where(function ($query) use ($identifier): void {
                    $query->where('email', $identifier)->orWhere('username', $identifier);
                })->whereNull('deleted_at')->first();

            return $user instanceof UserModel ? $this->tenantRecord($user) : null;
        });
    }

    private function lookupPlatformForLogin(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return $this->executionContext->runAsControlPlane(function () use ($email): ?array {
            $operator = PlatformOperatorModel::query()->where('email', $email)->first();
            return $operator instanceof PlatformOperatorModel ? $this->platformRecord($operator) : null;
        });
    }

    /** @return array{id:int,tenant_id:int,first_name:string,last_name:?string,email:string,username:?string,status:string,credentials_ready:bool} */
    private function tenantRecord(UserModel $user): array
    {
        return [
            'id' => (int) $user->getKey(), 'tenant_id' => (int) $user->getAttribute('tenant_id'),
            'first_name' => (string) $user->getAttribute('first_name'),
            'last_name' => $this->nullableString($user->getAttribute('last_name')),
            'email' => (string) $user->getAttribute('email'),
            'username' => $this->nullableString($user->getAttribute('username')),
            'status' => (string) $user->getAttribute('status'),
            'credentials_ready' => $user->getAttribute('credentials_ready_at') !== null,
        ];
    }

    /** @return array{id:int,first_name:string,last_name:?string,email:string,status:string,credentials_ready:bool} */
    private function platformRecord(PlatformOperatorModel $operator): array
    {
        return [
            'id' => (int) $operator->getKey(), 'first_name' => (string) $operator->getAttribute('first_name'),
            'last_name' => $this->nullableString($operator->getAttribute('last_name')),
            'email' => (string) $operator->getAttribute('email'),
            'status' => (string) $operator->getAttribute('status'),
            'credentials_ready' => $operator->getAttribute('credentials_ready_at') !== null,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
