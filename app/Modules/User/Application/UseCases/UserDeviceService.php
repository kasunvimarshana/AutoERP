<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\Core\Application\Results\Result;
use Modules\User\Application\Contracts\UseCases\UserDeviceServiceInterface;
use Modules\User\Application\Repositories\UserDeviceRepositoryInterface;
use Modules\User\Domain\Constants\UserErrorCode;
use Modules\User\Domain\Contracts\UserDomainServiceInterface;
use Throwable;

final class UserDeviceService extends AbstractUserCrudService implements UserDeviceServiceInterface
{
    public function __construct(
        private readonly UserDeviceRepositoryInterface $devices,
        private readonly UserDomainServiceInterface $domain,
    ) {
    }

    public function list(array $filters): Result
    {
        try {
            return $this->success($this->devices->list($this->criteria($filters)));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->devices->findById($id);

            return $record === null ? $this->notFound('User device not found.') : $this->success($record);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? null);
            $userId = (int) ($payload['user_id'] ?? 0);
            $deviceToken = trim((string) ($payload['device_token'] ?? ''));

            if ($this->devices->findByTenantUserDeviceToken($tenantId, $userId, $deviceToken) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_USER_DEVICE, 'User device token already exists in tenant scope.');
            }

            return $this->success($this->devices->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? null),
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'user_id' => $userId,
                'device_token' => $deviceToken,
                'platform' => $this->domain->normalizeNullableString($payload['platform'] ?? null),
                'device_name' => $this->domain->normalizeNullableString($payload['device_name'] ?? null),
                'last_active_at' => $this->domain->normalizeNullableString($payload['last_active_at'] ?? null),
                'row_version' => 1,
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->devices->findById($id);
            if ($existing === null) {
                return $this->notFound('User device not found.');
            }

            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? $existing->get('tenant_id'));
            $userId = array_key_exists('user_id', $payload) ? (int) $payload['user_id'] : (int) $existing->get('user_id');
            $deviceToken = array_key_exists('device_token', $payload)
                ? trim((string) $payload['device_token'])
                : (string) $existing->get('device_token');

            if ($this->devices->findByTenantUserDeviceToken($tenantId, $userId, $deviceToken, (int) $existing->id()) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_USER_DEVICE, 'User device token already exists in tenant scope.');
            }

            return $this->success($this->devices->update($id, [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? $existing->get('organization_unit_id')),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $existing->get('metadata'),
                'user_id' => $userId,
                'device_token' => $deviceToken,
                'platform' => array_key_exists('platform', $payload)
                    ? $this->domain->normalizeNullableString($payload['platform'])
                    : $existing->get('platform'),
                'device_name' => array_key_exists('device_name', $payload)
                    ? $this->domain->normalizeNullableString($payload['device_name'])
                    : $existing->get('device_name'),
                'last_active_at' => array_key_exists('last_active_at', $payload)
                    ? $this->domain->normalizeNullableString($payload['last_active_at'])
                    : $existing->get('last_active_at'),
                'row_version' => (int) $existing->get('row_version', 1) + 1,
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            if (! $this->devices->delete($id)) {
                return $this->notFound('User device not found.');
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

        foreach (['tenant_id', 'user_id', 'platform'] as $key) {
            if (! array_key_exists($key, $filters)) {
                continue;
            }

            if ($key === 'platform') {
                $value = $this->domain->normalizeNullableString((string) $filters[$key]);
                if ($value !== null) {
                    $criteria[$key] = $value;
                }

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
