<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Constants\AuthErrorCode;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\User\Repositories\RolePermissionRepositoryInterface;
use Modules\User\Repositories\UserPermissionRepositoryInterface;
use Modules\User\Repositories\UserRepositoryInterface;
use Modules\User\Repositories\UserRoleRepositoryInterface;

final class GetCurrentAuthProfileService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantPlanRepositoryInterface $tenantPlans,
        private readonly OrganizationUnitRepositoryInterface $organizationUnits,
        private readonly UserRoleRepositoryInterface $userRoles,
        private readonly UserPermissionRepositoryInterface $userPermissions,
        private readonly RolePermissionRepositoryInterface $rolePermissions,
    ) {}

    public function getProfile(
        int $userId,
        ?int $tenantId,
        ?int $organizationUnitId,
        ?string $guard,
        ?string $provider,
        ?string $applicationId,
        array $tokenPayload,
    ): Result {
        if ($userId < 1) {
            return $this->failure(AuthErrorCode::UNAUTHORIZED_ACCESS, 'Authenticated user id is invalid.');
        }

        $user = $this->users->findById($userId);
        if ($user === null) {
            return $this->failure(AuthErrorCode::UNAUTHORIZED_ACCESS, 'Authenticated user was not found.');
        }

        $status = strtolower(trim((string) $user->get('status', '')));
        if ($status !== 'active') {
            return $this->failure(AuthErrorCode::UNAUTHORIZED_ACCESS, 'Authenticated user is not active.');
        }

        $userTenantId = $this->toNullableInt($user->get('tenant_id'));
        if ($tenantId !== null && $userTenantId !== null && $tenantId !== $userTenantId) {
            return $this->failure(AuthErrorCode::TENANT_MISMATCH, 'Authenticated user does not belong to the requested tenant.');
        }

        $resolvedTenantId = $tenantId ?? $userTenantId;
        $resolvedOrganizationUnitId = $organizationUnitId ?? $this->toNullableInt($user->get('organization_unit_id'));
        $roleSummaries = $this->userRoles->listRoleSummariesForTenantUser($resolvedTenantId, $userId);
        $roleIds = array_map(static fn (array $role): int => (int) $role['id'], $roleSummaries);
        $roles = array_values(array_unique(array_map(
            static fn (array $role): string => (string) $role['name'],
            $roleSummaries,
        )));
        $directPermissions = $this->userPermissions->listPermissionNamesForTenantUser($resolvedTenantId, $userId);
        $rolePermissions = $this->rolePermissions->listPermissionNamesForTenantRoles($resolvedTenantId, $roleIds);
        $permissions = array_values(array_unique(array_merge($directPermissions, $rolePermissions)));
        sort($permissions);

        return Result::success([
            'user_id' => $userId,
            'tenant_id' => $resolvedTenantId,
            'organization_unit_id' => $resolvedOrganizationUnitId,
            'guard' => $guard,
            'provider' => $provider,
            'application_id' => $applicationId,
            'roles' => $roles,
            'permissions' => $permissions,
            'user' => [
                'id' => $userId,
                'tenant_id' => $resolvedTenantId,
                'organization_unit_id' => $resolvedOrganizationUnitId,
                'first_name' => (string) $user->get('first_name', ''),
                'last_name' => $this->nullableString($user->get('last_name')),
                'email' => (string) $user->get('email', ''),
                'status' => $status,
                'roles' => $roles,
                'permissions' => $permissions,
                'metadata' => $this->safeMetadata($user->get('metadata')),
            ],
            'tenant' => $this->tenantSummary($resolvedTenantId),
            'organization_unit' => $this->organizationUnitSummary($resolvedOrganizationUnitId),
            'token_payload' => $this->sanitizeTokenPayload($tokenPayload),
        ]);
    }

    private function failure(string $code, string $message): Result
    {
        return Result::failure(new Error($code, $message));
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function tenantSummary(?int $tenantId): ?array
    {
        if ($tenantId === null) {
            return null;
        }

        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null) {
            return ['id' => $tenantId, 'name' => null];
        }

        $metadata = $this->arrayValue($tenant->get('metadata', []));
        $planId = $this->toNullableInt($tenant->get('tenant_plan_id'));
        $plan = $planId !== null ? $this->tenantPlans->findById($planId) : null;
        $features = $plan?->get('features');

        return [
            'id' => (int) $tenant->id(),
            'name' => $this->nullableString($tenant->get('name')),
            'features' => is_array($features) ? $features : [],
            'enabled_modules' => $this->stringList($metadata['enabled_modules'] ?? []),
        ];
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => is_scalar($item) ? trim((string) $item) : '',
            $value,
        )));
    }

    /**
     * @return array<string,mixed>
     */
    private function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function organizationUnitSummary(?int $organizationUnitId): ?array
    {
        if ($organizationUnitId === null) {
            return null;
        }

        $organizationUnit = $this->organizationUnits->findById($organizationUnitId);
        if ($organizationUnit === null) {
            return ['id' => $organizationUnitId, 'name' => null];
        }

        return [
            'id' => (int) $organizationUnit->id(),
            'name' => $this->nullableString($organizationUnit->get('name')),
            'enabled_modules' => $this->stringList(
                $this->arrayValue($organizationUnit->get('metadata'))['enabled_modules'] ?? [],
            ),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function safeMetadata(mixed $metadata): array
    {
        if (! is_array($metadata)) {
            return [];
        }

        unset($metadata['password'], $metadata['password_hash'], $metadata['token'], $metadata['token_hash']);

        return $metadata;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function sanitizeTokenPayload(array $payload): array
    {
        unset(
            $payload['token_hash'],
            $payload['refresh_hash'],
            $payload['access_token'],
            $payload['refresh_token'],
            $payload['password'],
        );

        return $payload;
    }
}
