<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Core\Contracts\OrganizationUnitDirectoryInterface;
use Modules\Core\Contracts\TenantAuthenticationDirectoryInterface;
use Modules\User\Contracts\PlatformOperatorAuthenticationDirectoryInterface;
use Modules\User\Contracts\TenantUserAuthenticationDirectoryInterface;

final readonly class AuthProfileService
{
    public function __construct(
        private TenantAuthenticationDirectoryInterface $tenants,
        private TenantUserAuthenticationDirectoryInterface $tenantUsers,
        private PlatformOperatorAuthenticationDirectoryInterface $operators,
        private OrganizationUnitDirectoryInterface $organizationUnits,
    ) {}

    /** @param array<string,mixed> $token @return array<string,mixed> */
    public function tenant(array $token): array
    {
        $tenantId = $this->positiveInt($token['tenant_id'] ?? null);
        $userId = $this->positiveInt($token['tenant_user_id'] ?? null);
        $organizationUnitId = $this->positiveInt($token['organization_unit_id'] ?? null);
        if ($tenantId === null || $userId === null || $organizationUnitId === null) {
            throw $this->unauthorized();
        }

        $user = $this->tenantUsers->findActiveTenantById($tenantId, $userId);
        $tenant = $this->tenants->findActive($tenantId);
        $organizationUnit = $this->organizationUnits->summaries($tenantId, [$organizationUnitId])[$organizationUnitId] ?? null;
        if ($user === null || $tenant === null || $organizationUnit === null
            || ! $this->tenantUsers->canAccessOrganizationUnit($tenantId, $userId, $organizationUnitId)) {
            throw $this->unauthorized();
        }

        $roles = $this->tenantUsers->roleNames($tenantId, $userId);
        $permissions = $this->tenantUsers->permissionNames($tenantId, $userId);

        return [
            'user' => array_merge($user, ['roles' => $roles, 'permissions' => $permissions]),
            'tenant' => $tenant,
            'organization_unit' => $organizationUnit,
            'roles' => $roles,
            'permissions' => $permissions,
            'enabled_modules' => $this->tenants->enabledModules($tenantId),
            'is_platform_operator' => false,
        ];
    }

    /** @param array<string,mixed> $token @return array<string,mixed> */
    public function platform(array $token): array
    {
        $operatorId = $this->positiveInt($token['platform_operator_id'] ?? null);
        $operator = $operatorId === null ? null : $this->operators->findActivePlatformById($operatorId);
        if ($operatorId === null || $operator === null) {
            throw new AuthFailure(
                AuthErrorCode::UNAUTHORIZED_ACCESS,
                'Platform authentication is required.',
                401,
            );
        }

        $permissions = $this->operators->permissionNames($operatorId);

        return [
            'user' => array_merge($operator, [
                'roles' => ['Platform Operator'],
                'permissions' => $permissions,
                'is_platform_operator' => true,
            ]),
            'tenant' => null,
            'organization_unit' => null,
            'roles' => ['Platform Operator'],
            'permissions' => $permissions,
            'enabled_modules' => null,
            'is_platform_operator' => true,
        ];
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_int($value) && ! ctype_digit((string) $value)) {
            return null;
        }
        $value = (int) $value;
        return $value > 0 ? $value : null;
    }

    private function unauthorized(): AuthFailure
    {
        return new AuthFailure(AuthErrorCode::UNAUTHORIZED_ACCESS, 'Authentication is required.', 401);
    }
}
