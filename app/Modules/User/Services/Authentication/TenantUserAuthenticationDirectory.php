<?php

declare(strict_types=1);

namespace Modules\User\Services\Authentication;

use Modules\Core\Contracts\OrganizationUnitUserAccessCheckerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\UserStatus;
use Modules\User\Contracts\TenantUserAuthenticationDirectoryInterface;
use Modules\User\Models\UserModel;
use Modules\User\Repositories\UserRoleRepositoryInterface;
use Modules\User\Services\UserAccessResolver;

final class TenantUserAuthenticationDirectory implements TenantUserAuthenticationDirectoryInterface
{
    public function __construct(
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly UserAccessResolver $accessResolver,
        private readonly OrganizationUnitUserAccessCheckerInterface $organizationUnitAccess,
        private readonly UserRoleRepositoryInterface $roles,
    ) {}

    public function findTenantForLogin(int $tenantId, string $identifier): ?array
    {
        $identifier = strtolower(trim($identifier));
        if ($tenantId < 1 || $identifier === '') {
            return null;
        }

        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $identifier): ?array {
            $user = UserModel::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($query) use ($identifier): void {
                    $query->where('email', $identifier)->orWhere('username', $identifier);
                })
                ->whereNull('deleted_at')
                ->first();

            return $user instanceof UserModel ? $this->record($user) : null;
        });
    }

    public function findActiveTenantById(int $tenantId, int $userId): ?array
    {
        if ($tenantId < 1 || $userId < 1) {
            return null;
        }

        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $userId): ?array {
            $user = UserModel::query()
                ->whereKey($userId)
                ->where('tenant_id', $tenantId)
                ->where('status', UserStatus::ACTIVE)
                ->whereNotNull('credentials_ready_at')
                ->whereNull('deleted_at')
                ->first();

            return $user instanceof UserModel ? $this->record($user) : null;
        });
    }

    public function resolveLoginOrganizationUnit(
        int $tenantId,
        int $userId,
        ?int $requestedOrganizationUnitId,
    ): ?int {
        if ($tenantId < 1 || $userId < 1 || ($requestedOrganizationUnitId !== null && $requestedOrganizationUnitId < 1)) {
            return null;
        }

        return $this->executionContext->runForTenant($tenantId, function () use (
            $tenantId,
            $userId,
            $requestedOrganizationUnitId,
        ): ?int {
            $user = UserModel::query()
                ->whereKey($userId)
                ->where('tenant_id', $tenantId)
                ->where('status', UserStatus::ACTIVE)
                ->whereNotNull('credentials_ready_at')
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();
            if (! $user instanceof UserModel) {
                return null;
            }

            $organizationUnitIds = $requestedOrganizationUnitId !== null
                ? [$requestedOrganizationUnitId]
                : $this->accessResolver->defaultOrganizationUnitIds($userId, $tenantId);
            if (count($organizationUnitIds) !== 1) {
                return null;
            }

            $organizationUnitId = (int) $organizationUnitIds[0];

            return $this->organizationUnitAccess->canAccessOrganizationUnit(
                $userId,
                $tenantId,
                $organizationUnitId,
                true,
            ) ? $organizationUnitId : null;
        });
    }

    public function defaultOrganizationUnitIds(int $tenantId, int $userId): array
    {
        return $this->accessResolver->defaultOrganizationUnitIds($userId, $tenantId);
    }

    public function canAccessOrganizationUnit(
        int $tenantId,
        int $userId,
        int $organizationUnitId,
        bool $lockForUpdate = false,
    ): bool {
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

    public function permissionNames(int $tenantId, int $userId): array
    {
        return $this->accessResolver->effectivePermissionNames($userId, $tenantId);
    }

    /** @return array{id:int,tenant_id:int,first_name:string,last_name:?string,email:string,username:?string,status:string,credentials_ready:bool} */
    private function record(UserModel $user): array
    {
        return [
            'id' => (int) $user->getKey(),
            'tenant_id' => (int) $user->getAttribute('tenant_id'),
            'first_name' => (string) $user->getAttribute('first_name'),
            'last_name' => $this->nullableString($user->getAttribute('last_name')),
            'email' => (string) $user->getAttribute('email'),
            'username' => $this->nullableString($user->getAttribute('username')),
            'status' => (string) $user->getAttribute('status'),
            'credentials_ready' => $user->getAttribute('credentials_ready_at') !== null,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
