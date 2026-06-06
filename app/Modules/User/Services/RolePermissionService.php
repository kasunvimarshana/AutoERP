<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Modules\Core\Results\Result;
use Modules\User\Constants\UserErrorCode;
use Modules\User\Repositories\RolePermissionRepositoryInterface;
use Modules\User\Services\Contracts\UserDomainServiceInterface;
use Throwable;

final class RolePermissionService extends AbstractUserCrudService
{
    public function __construct(
        private readonly RolePermissionRepositoryInterface $rolePermissions,
        private readonly UserDomainServiceInterface $domain,
    ) {}

    public function list(array $filters): Result
    {
        try {
            return $this->success($this->rolePermissions->list($this->criteria($filters)));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->rolePermissions->findById($id);

            return $record === null ? $this->notFound('Role permission not found.') : $this->success($record);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? null);
            $roleId = (int) ($payload['role_id'] ?? 0);
            $permissionId = (int) ($payload['permission_id'] ?? 0);

            if ($this->rolePermissions->findByTenantRolePermission($tenantId, $roleId, $permissionId) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_ROLE_PERMISSION, 'Role permission mapping already exists.');
            }

            return $this->success($this->rolePermissions->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? null),
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'row_version' => 1,
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->rolePermissions->findById($id);
            if ($existing === null) {
                return $this->notFound('Role permission not found.');
            }

            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? $existing->get('tenant_id'));
            $roleId = array_key_exists('role_id', $payload) ? (int) $payload['role_id'] : (int) $existing->get('role_id');
            $permissionId = array_key_exists('permission_id', $payload)
                ? (int) $payload['permission_id']
                : (int) $existing->get('permission_id');

            if ($this->rolePermissions->findByTenantRolePermission($tenantId, $roleId, $permissionId, (int) $existing->id()) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_ROLE_PERMISSION, 'Role permission mapping already exists.');
            }

            return $this->success($this->rolePermissions->update($id, [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? $existing->get('organization_unit_id')),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $existing->get('metadata'),
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'row_version' => (int) $existing->get('row_version', 1) + 1,
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            if (! $this->rolePermissions->delete($id)) {
                return $this->notFound('Role permission not found.');
            }

            return $this->success(true);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function criteria(array $filters): array
    {
        $criteria = [];

        foreach (['tenant_id', 'role_id', 'permission_id'] as $key) {
            if (! array_key_exists($key, $filters)) {
                continue;
            }

            $value = $this->toNullableInt($filters[$key]);
            if ($value === null && $key !== 'tenant_id') {
                continue;
            }

            $criteria[$key] = $value;
        }

        return $criteria;
    }
}
