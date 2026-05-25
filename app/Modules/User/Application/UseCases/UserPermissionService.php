<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\Core\Application\Results\Result;
use Modules\User\Application\Contracts\UseCases\UserPermissionServiceInterface;
use Modules\User\Application\Repositories\UserPermissionRepositoryInterface;
use Modules\User\Domain\Constants\UserErrorCode;
use Modules\User\Domain\Contracts\UserDomainServiceInterface;
use Throwable;

final class UserPermissionService extends AbstractUserCrudService implements UserPermissionServiceInterface
{
    public function __construct(
        private readonly UserPermissionRepositoryInterface $userPermissions,
        private readonly UserDomainServiceInterface $domain,
    ) {
    }

    public function list(array $filters): Result
    {
        try {
            return $this->success($this->userPermissions->list($this->criteria($filters)));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->userPermissions->findById($id);

            return $record === null ? $this->notFound('User permission not found.') : $this->success($record);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? null);
            $userId = (int) ($payload['user_id'] ?? 0);
            $permissionId = (int) ($payload['permission_id'] ?? 0);

            if ($this->userPermissions->findByTenantUserPermission($tenantId, $userId, $permissionId) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_USER_PERMISSION, 'User permission mapping already exists.');
            }

            return $this->success($this->userPermissions->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? null),
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'user_id' => $userId,
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
            $existing = $this->userPermissions->findById($id);
            if ($existing === null) {
                return $this->notFound('User permission not found.');
            }

            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? $existing->get('tenant_id'));
            $userId = array_key_exists('user_id', $payload) ? (int) $payload['user_id'] : (int) $existing->get('user_id');
            $permissionId = array_key_exists('permission_id', $payload)
                ? (int) $payload['permission_id']
                : (int) $existing->get('permission_id');

            if ($this->userPermissions->findByTenantUserPermission($tenantId, $userId, $permissionId, (int) $existing->id()) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_USER_PERMISSION, 'User permission mapping already exists.');
            }

            return $this->success($this->userPermissions->update($id, [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? $existing->get('organization_unit_id')),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $existing->get('metadata'),
                'user_id' => $userId,
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
            if (! $this->userPermissions->delete($id)) {
                return $this->notFound('User permission not found.');
            }

            return $this->success(true);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function criteria(array $filters): array
    {
        $criteria = [];

        foreach (['tenant_id', 'user_id', 'permission_id'] as $key) {
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
