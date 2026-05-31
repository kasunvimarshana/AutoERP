<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\UseCases\GetCurrentAuthProfileServiceInterface;
use Modules\Auth\Domain\Constants\AuthErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\User\Application\Repositories\RolePermissionRepositoryInterface;
use Modules\User\Application\Repositories\UserPermissionRepositoryInterface;
use Modules\User\Application\Repositories\UserRepositoryInterface;
use Modules\User\Application\Repositories\UserRoleRepositoryInterface;

final class GetCurrentAuthProfileService implements GetCurrentAuthProfileServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserRoleRepositoryInterface $userRoles,
        private readonly UserPermissionRepositoryInterface $userPermissions,
        private readonly RolePermissionRepositoryInterface $rolePermissions,
    ) {
    }

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
     * @param array<string,mixed> $payload
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
