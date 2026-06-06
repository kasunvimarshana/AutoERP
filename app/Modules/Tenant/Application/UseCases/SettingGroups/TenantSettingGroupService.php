<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases\SettingGroups;

use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantSettingGroupRepositoryInterface;
use Modules\Tenant\Application\Support\TenantContext;
use Modules\Tenant\Domain\Constants\TenantErrorCode;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface;
use Throwable;

final class TenantSettingGroupService
{
    public function __construct(
        private readonly TenantSettingGroupRepositoryInterface $groups,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainServiceInterface $domain,
        private readonly TenantContext $tenantContext,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function listByTenant(int|string $tenantId): Result
    {
        try {
            $resolvedTenantId = $this->tenantContext->resolveTenantId($this->toNullableInt($tenantId));

            return Result::success($this->groups->listByTenant($resolvedTenantId));
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.setting-groups.list'],
            ));
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->groups->findById($id);
            if ($record === null || ! $this->isRecordInTenantScope($record)) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant setting group not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.setting-groups.get', 'group_id' => (string) $id],
            ));
        }
    }

    public function create(array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($payload): Result {
                $tenantId = $this->tenantContext->resolveTenantId($this->toNullableInt($payload['tenant_id'] ?? null));
                if ($this->tenants->findById($tenantId) === null) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
                }

                $key = $this->domain->normalizeKey((string) ($payload['key'] ?? ''));
                if ($this->groups->findByTenantAndKey($tenantId, $key) !== null) {
                    return Result::failure(
                        new Error(TenantErrorCode::CONFLICT, 'Tenant setting group key already exists.'),
                    );
                }

                $parentId = isset($payload['parent_id']) ? (int) $payload['parent_id'] : null;
                if ($parentId !== null) {
                    $parent = $this->groups->findById($parentId);
                    if ($parent === null || (int) $parent->require('tenant_id') !== $tenantId) {
                        return Result::failure(
                            new Error(TenantErrorCode::CONFLICT, 'Parent group must belong to same tenant.'),
                        );
                    }
                }

                $record = $this->groups->create([
                    'tenant_id' => $tenantId,
                    'key' => $key,
                    'value' => $this->domain->normalizeOptionalText(
                        isset($payload['value']) ? (string) $payload['value'] : null,
                    ),
                    'parent_id' => $parentId,
                    'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                    'row_version' => 1,
                ]);

                return Result::success($record);
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.setting-groups.create'],
            ));
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $payload): Result {
                $existing = $this->groups->findById($id);
                if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant setting group not found.'));
                }

                $tenantId = (int) $existing->require('tenant_id');
                $key = $this->domain->normalizeKey((string) ($payload['key'] ?? $existing->require('key')));
                $byKey = $this->groups->findByTenantAndKey($tenantId, $key);
                if ($byKey !== null && (string) $byKey->id() !== (string) $existing->id()) {
                    return Result::failure(
                        new Error(TenantErrorCode::CONFLICT, 'Tenant setting group key already exists.'),
                    );
                }

                if (array_key_exists('parent_id', $payload)) {
                    $parentId = isset($payload['parent_id']) ? (int) $payload['parent_id'] : null;
                } else {
                    $parentId = $existing->get('parent_id') !== null ? (int) $existing->get('parent_id') : null;
                }

                if ($parentId !== null) {
                    if ((string) $parentId === (string) $id) {
                        return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Group cannot parent itself.'));
                    }

                    $parent = $this->groups->findById($parentId);
                    if ($parent === null || (int) $parent->require('tenant_id') !== $tenantId) {
                        return Result::failure(
                            new Error(TenantErrorCode::CONFLICT, 'Parent group must belong to same tenant.'),
                        );
                    }
                }

                $record = $this->groups->update($id, [
                    'key' => $key,
                    'value' => array_key_exists('value', $payload)
                        ? $this->domain->normalizeOptionalText(
                            isset($payload['value']) ? (string) $payload['value'] : null,
                        )
                        : $existing->get('value'),
                    'parent_id' => $parentId,
                    'metadata' => array_key_exists('metadata', $payload)
                        ? $this->domain->normalizeMetadata($payload['metadata'])
                        : $this->domain->normalizeMetadata($existing->get('metadata')),
                    'row_version' => ((int) $existing->get('row_version', 1)) + 1,
                ]);

                return Result::success($record);
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.setting-groups.update', 'group_id' => (string) $id],
            ));
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id): Result {
                $existing = $this->groups->findById($id);
                if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant setting group not found.'));
                }

                return Result::success($this->groups->delete($id));
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.setting-groups.delete', 'group_id' => (string) $id],
            ));
        }
    }

    private function isRecordInTenantScope(DataRecord $record): bool
    {
        return (int) $record->require('tenant_id') === $this->tenantContext->requireTenantId();
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
