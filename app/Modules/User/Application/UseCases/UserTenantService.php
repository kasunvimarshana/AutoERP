<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\Core\Application\Results\Result;
use Modules\User\Application\Contracts\UseCases\UserTenantServiceInterface;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use Modules\User\Domain\Constants\UserErrorCode;
use Modules\User\Domain\Contracts\UserDomainServiceInterface;
use Throwable;

final class UserTenantService extends AbstractUserCrudService implements UserTenantServiceInterface
{
    public function __construct(
        private readonly UserTenantRepositoryInterface $userTenants,
        private readonly UserDomainServiceInterface $domain,
    ) {
    }

    public function list(array $filters): Result
    {
        try {
            return $this->success($this->userTenants->list($this->criteria($filters)));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->userTenants->findById($id);

            return $record === null ? $this->notFound('User tenant record not found.') : $this->success($record);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            $organizationUnitId = $this->toNullableInt($payload['organization_unit_id'] ?? null);
            $userId = (int) ($payload['user_id'] ?? 0);

            if ($this->userTenants->findByTenantOrganizationUser($tenantId, $organizationUnitId, $userId) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_USER_TENANT, 'User tenant mapping already exists.');
            }

            $isDefault = $this->toBool($payload['is_default'] ?? false);
            if ($isDefault) {
                $this->userTenants->clearDefaultForUser($tenantId, $userId);
            }

            return $this->success($this->userTenants->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'user_id' => $userId,
                'role_id' => $this->toNullableInt($payload['role_id'] ?? null),
                'is_default' => $isDefault,
                'row_version' => 1,
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->userTenants->findById($id);
            if ($existing === null) {
                return $this->notFound('User tenant record not found.');
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $existing->get('tenant_id'));
            $organizationUnitId = $this->toNullableInt($payload['organization_unit_id'] ?? $existing->get('organization_unit_id'));
            $userId = (int) ($payload['user_id'] ?? $existing->get('user_id'));

            if ($this->userTenants->findByTenantOrganizationUser($tenantId, $organizationUnitId, $userId, (int) $existing->id()) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_USER_TENANT, 'User tenant mapping already exists.');
            }

            $isDefault = array_key_exists('is_default', $payload)
                ? $this->toBool($payload['is_default'])
                : (bool) $existing->get('is_default', false);

            if ($isDefault) {
                $this->userTenants->clearDefaultForUser($tenantId, $userId, (int) $existing->id());
            }

            return $this->success($this->userTenants->update($id, [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $existing->get('metadata'),
                'user_id' => $userId,
                'role_id' => array_key_exists('role_id', $payload)
                    ? $this->toNullableInt($payload['role_id'])
                    : $this->toNullableInt($existing->get('role_id')),
                'is_default' => $isDefault,
                'row_version' => (int) $existing->get('row_version', 1) + 1,
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            if (! $this->userTenants->delete($id)) {
                return $this->notFound('User tenant record not found.');
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

        foreach (['tenant_id', 'organization_unit_id', 'user_id', 'role_id'] as $key) {
            if (! array_key_exists($key, $filters)) {
                continue;
            }

            $value = $this->toNullableInt($filters[$key]);
            if ($value === null && $key !== 'organization_unit_id' && $key !== 'role_id') {
                continue;
            }

            $criteria[$key] = $value;
        }

        return $criteria;
    }
}
