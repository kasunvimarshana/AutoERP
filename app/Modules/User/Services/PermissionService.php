<?php

declare(strict_types=1);

namespace Modules\User\Services;

use InvalidArgumentException;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserErrorCode;
use Modules\User\Repositories\PermissionRepositoryInterface;
use Modules\User\Services\Contracts\UserDomainServiceInterface;
use Throwable;

final class PermissionService extends AbstractUserCrudService
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissions,
        private readonly UserDomainServiceInterface $domain,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    public function list(array $filters): Result
    {
        try {
            $perPage = max(1, (int) ($filters['per_page'] ?? 15));
            $page = max(1, (int) ($filters['page'] ?? 1));
            $tenantId = $this->resolveTenantId($this->toNullableInt($filters['tenant_id'] ?? null));
            $module = $this->domain->normalizeNullableString($filters['module'] ?? null);
            $search = $this->domain->normalizeNullableString((string) ($filters['search'] ?? ''));
            $result = $this->permissions->pageByFilters($tenantId, $module, $search, $perPage, $page);

            return $this->success(new PagedResult(
                array_map(fn (DataRecord $record): DataRecord => $this->withCatalogueFields($record), $result->items),
                $result->total,
                $result->page,
                $result->perPage,
            ));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->permissions->findById($id);
            if ($record === null || (int) $record->require('tenant_id') !== $this->resolveTenantId(null)) {
                return $this->notFound('Permission not found.');
            }

            return $this->success($this->withCatalogueFields($record));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? null);
            $name = $this->domain->normalizeRequiredString((string) ($payload['name'] ?? ''), 'Permission name');
            $guardName = $this->domain->normalizeNullableString($payload['guard_name'] ?? null)
                ?? (string) config('auth.defaults.guard', 'api');

            if ($this->permissions->findByTenantNameGuard($tenantId, $name, $guardName) !== null) {
                return $this->failure(
                    UserErrorCode::DUPLICATE_PERMISSION,
                    'Permission already exists in tenant scope.',
                );
            }

            return $this->success($this->permissions->create([
                'tenant_id' => $tenantId,
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'name' => $name,
                'guard_name' => $guardName,
                'module' => $this->domain->normalizeNullableString($payload['module'] ?? null),
                'description' => $this->domain->normalizeNullableString($payload['description'] ?? null),
                'row_version' => 1,
            ]));
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->permissions->findById($id);
            if ($existing === null) {
                return $this->notFound('Permission not found.');
            }

            $recordId = (int) $existing->id();
            $tenantId = $this->toNullableInt($payload['tenant_id'] ?? $existing->get('tenant_id'));
            $name = array_key_exists('name', $payload)
                ? $this->domain->normalizeRequiredString(
                    (string) $payload['name'],
                    'Permission name',
                )
                : (string) $existing->get('name');
            $guardName = array_key_exists('guard_name', $payload)
                ? ($this->domain->normalizeNullableString($payload['guard_name']) ?? 'api')
                : (string) $existing->get('guard_name', 'api');

            if ($this->permissions->findByTenantNameGuard($tenantId, $name, $guardName, $recordId) !== null) {
                return $this->failure(
                    UserErrorCode::DUPLICATE_PERMISSION,
                    'Permission already exists in tenant scope.',
                );
            }

            return $this->success(
                $this->permissions->update($id, [
                    'tenant_id' => $tenantId,
                    'metadata' => array_key_exists('metadata', $payload)
                        ? $this->domain->normalizeMetadata($payload['metadata'])
                        : $existing->get('metadata'),
                    'name' => $name,
                    'guard_name' => $guardName,
                    'module' => array_key_exists('module', $payload)
                        ? $this->domain->normalizeNullableString($payload['module'])
                        : $existing->get('module'),
                    'description' => array_key_exists('description', $payload)
                        ? $this->domain->normalizeNullableString($payload['description'])
                        : $existing->get('description'),
                    'row_version' => (int) $existing->get('row_version', 1) + 1,
                ]),
            );
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            if (! $this->permissions->delete($id)) {
                return $this->notFound('Permission not found.');
            }

            return $this->success(true);
        } catch (Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    private function resolveTenantId(?int $requestedTenantId): int
    {
        $currentTenantId = $this->currentTenant->currentTenantId();
        if ($currentTenantId !== null && $requestedTenantId !== null && $requestedTenantId !== $currentTenantId) {
            throw new InvalidArgumentException('Tenant scope mismatch for permission operation.');
        }

        $resolvedTenantId = $requestedTenantId ?? $currentTenantId;
        if ($resolvedTenantId === null || $resolvedTenantId < 1) {
            throw new InvalidArgumentException('Tenant context is required for permission operations.');
        }

        return $resolvedTenantId;
    }

    private function withCatalogueFields(DataRecord $record): DataRecord
    {
        $payload = $record->toArray();
        $name = (string) ($payload['name'] ?? '');
        $segments = explode('.', $name);
        $payload['resource'] = $segments[0] ?? null;
        $payload['action'] = count($segments) > 1 ? implode('.', array_slice($segments, 1)) : null;
        $payload['status'] = 'system_defined';
        $payload['is_read_only'] = true;

        return new DataRecord($payload);
    }
}
