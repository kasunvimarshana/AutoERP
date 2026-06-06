<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Modules\Core\Results\Result;
use Modules\User\Constants\UserErrorCode;
use Modules\User\Repositories\UserRoleRepositoryInterface;
use Modules\User\Services\Contracts\UserDomainServiceInterface;
use Throwable;

final class UserRoleService extends AbstractUserCrudService
{
    public function __construct(
        private readonly UserRoleRepositoryInterface $userRoles,
        private readonly UserDomainServiceInterface $domain,
    ) {}

    public function list(array $filters): Result
    {
        try {
            return $this->success($this->userRoles->list($this->criteria($filters)));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->userRoles->findById($id);

            return $record === null ? $this->notFound('User role not found.') : $this->success($record);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? null);
            $userId = (int) ($payload['user_id'] ?? 0);
            $roleId = (int) ($payload['role_id'] ?? 0);

            if ($this->userRoles->findByTenantUserRole($tenantId, $userId, $roleId) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_USER_ROLE, 'User role mapping already exists.');
            }

            return $this->success($this->userRoles->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? null),
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'user_id' => $userId,
                'role_id' => $roleId,
                'row_version' => 1,
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->userRoles->findById($id);
            if ($existing === null) {
                return $this->notFound('User role not found.');
            }

            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? $existing->get('tenant_id'));
            $userId = array_key_exists('user_id', $payload) ? (int) $payload['user_id'] : (int) $existing->get('user_id');
            $roleId = array_key_exists('role_id', $payload) ? (int) $payload['role_id'] : (int) $existing->get('role_id');

            if ($this->userRoles->findByTenantUserRole($tenantId, $userId, $roleId, (int) $existing->id()) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_USER_ROLE, 'User role mapping already exists.');
            }

            return $this->success($this->userRoles->update($id, [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? $existing->get('organization_unit_id')),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $existing->get('metadata'),
                'user_id' => $userId,
                'role_id' => $roleId,
                'row_version' => (int) $existing->get('row_version', 1) + 1,
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            if (! $this->userRoles->delete($id)) {
                return $this->notFound('User role not found.');
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

        foreach (['tenant_id', 'user_id', 'role_id'] as $key) {
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
