<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\Core\Application\Results\Result;
use Modules\User\Application\Contracts\UseCases\UserDocumentServiceInterface;
use Modules\User\Application\Repositories\UserDocumentRepositoryInterface;
use Modules\User\Domain\Constants\UserErrorCode;
use Modules\User\Domain\Contracts\UserDomainServiceInterface;
use Throwable;

final class UserDocumentService extends AbstractUserCrudService implements UserDocumentServiceInterface
{
    public function __construct(
        private readonly UserDocumentRepositoryInterface $documents,
        private readonly UserDomainServiceInterface $domain,
    ) {
    }

    public function list(array $filters): Result
    {
        try {
            return $this->success($this->documents->list($this->criteria($filters)));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->documents->findById($id);

            return $record === null ? $this->notFound('User document not found.') : $this->success($record);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? null);
            $userId = (int) ($payload['user_id'] ?? 0);
            $name = trim((string) ($payload['name'] ?? ''));

            if ($this->documents->findByTenantUserName($tenantId, $userId, $name) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_USER_DOCUMENT, 'User document name already exists in tenant scope.');
            }

            return $this->success($this->documents->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? null),
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'user_id' => $userId,
                'name' => $name,
                'file_path' => trim((string) ($payload['file_path'] ?? '')),
                'mime_type' => $this->domain->normalizeNullableString($payload['mime_type'] ?? null),
                'size' => $this->toNullableInt($payload['size'] ?? null),
                'type' => $this->domain->normalizeNullableString($payload['type'] ?? null),
                'row_version' => 1,
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->documents->findById($id);
            if ($existing === null) {
                return $this->notFound('User document not found.');
            }

            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? $existing->get('tenant_id'));
            $userId = array_key_exists('user_id', $payload) ? (int) $payload['user_id'] : (int) $existing->get('user_id');
            $name = array_key_exists('name', $payload) ? trim((string) $payload['name']) : (string) $existing->get('name');

            if ($this->documents->findByTenantUserName($tenantId, $userId, $name, (int) $existing->id()) !== null) {
                return $this->failure(UserErrorCode::DUPLICATE_USER_DOCUMENT, 'User document name already exists in tenant scope.');
            }

            return $this->success($this->documents->update($id, [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $this->toNullableInt($payload['organization_unit_id'] ?? $existing->get('organization_unit_id')),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $existing->get('metadata'),
                'user_id' => $userId,
                'name' => $name,
                'file_path' => array_key_exists('file_path', $payload)
                    ? trim((string) $payload['file_path'])
                    : $existing->get('file_path'),
                'mime_type' => array_key_exists('mime_type', $payload)
                    ? $this->domain->normalizeNullableString($payload['mime_type'])
                    : $existing->get('mime_type'),
                'size' => array_key_exists('size', $payload)
                    ? $this->toNullableInt($payload['size'])
                    : $this->toNullableInt($existing->get('size')),
                'type' => array_key_exists('type', $payload)
                    ? $this->domain->normalizeNullableString($payload['type'])
                    : $existing->get('type'),
                'row_version' => (int) $existing->get('row_version', 1) + 1,
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            if (! $this->documents->delete($id)) {
                return $this->notFound('User document not found.');
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

        foreach (['tenant_id', 'user_id', 'type'] as $key) {
            if (! array_key_exists($key, $filters)) {
                continue;
            }

            if ($key === 'type') {
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
