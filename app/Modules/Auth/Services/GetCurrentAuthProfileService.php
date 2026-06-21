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

        $resolvedTenantId = $tenantId;
        $resolvedOrganizationUnitId = $organizationUnitId;
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
            'enabled_modules' => $this->enabledModules($resolvedTenantId),
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

        return [
            'id' => (int) $tenant->id(),
            'name' => $this->nullableString($tenant->get('name')),
            'timezone' => $this->timezoneFromMetadata($tenant->get('metadata')),
        ];
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
            'timezone' => $this->timezoneFromMetadata($organizationUnit->get('metadata')),
        ];
    }

    private function timezoneFromMetadata(mixed $metadata): ?string
    {
        if (! is_array($metadata)) {
            return null;
        }

        $timezone = $metadata['business_timezone'] ?? $metadata['timezone'] ?? null;
        if (! is_string($timezone) || ! in_array($timezone, timezone_identifiers_list(), true)) {
            return null;
        }

        return $timezone;
    }

    /**
     * @return list<string>|null
     */
    private function enabledModules(?int $tenantId): ?array
    {
        if ($tenantId === null) {
            return null;
        }

        $tenant = $this->tenants->findById($tenantId);
        $planId = $tenant?->get('tenant_plan_id');
        if (! is_numeric($planId) || (int) $planId < 1) {
            return null;
        }

        $plan = $this->tenantPlans->findById((int) $planId);
        $features = $plan?->get('features');
        if (! is_array($features)) {
            return null;
        }

        $configured = $features['enabled_modules'] ?? $features['modules'] ?? null;
        if (! is_array($configured)) {
            return null;
        }

        $modules = [];
        foreach ($configured as $key => $value) {
            if (is_int($key) && is_scalar($value)) {
                $modules[] = strtolower(trim((string) $value));

                continue;
            }

            if (is_string($key) && filter_var($value, FILTER_VALIDATE_BOOL)) {
                $modules[] = strtolower(trim($key));
            }
        }

        return array_values(array_unique(array_filter($modules)));
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
