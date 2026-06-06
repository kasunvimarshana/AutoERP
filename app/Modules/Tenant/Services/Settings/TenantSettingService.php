<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Settings;

use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Repositories\TenantSettingGroupRepositoryInterface;
use Modules\Tenant\Repositories\TenantSettingRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface;
use Modules\Tenant\Support\TenantContext;
use Throwable;

final class TenantSettingService
{
    public function __construct(
        private readonly TenantSettingRepositoryInterface $settings,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantSettingGroupRepositoryInterface $groups,
        private readonly TenantDomainServiceInterface $domain,
        private readonly TenantContext $tenantContext,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function listByTenant(int|string $tenantId): Result
    {
        try {
            $resolvedTenantId = $this->tenantContext->resolveTenantId($this->toNullableInt($tenantId));

            return Result::success($this->settings->listByTenant($resolvedTenantId));
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.settings.list'],
            ));
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->settings->findById($id);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant setting not found.'));
            }

            if (! $this->isRecordInTenantScope($record)) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant setting not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.settings.get', 'setting_id' => (string) $id],
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

                $groupId = (int) ($payload['group_id'] ?? 0);
                $group = $this->groups->findById($groupId);
                if ($groupId < 1 || $group === null || (int) $group->require('tenant_id') !== $tenantId) {
                    return Result::failure(
                        new Error(TenantErrorCode::CONFLICT, 'Setting group must belong to same tenant.'),
                    );
                }

                $key = $this->domain->normalizeKey((string) ($payload['key'] ?? ''));
                if ($this->settings->findByTenantAndKey($tenantId, $key) !== null) {
                    return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant setting key already exists.'));
                }

                $record = $this->settings->create([
                    'tenant_id' => $tenantId,
                    'group_id' => $groupId,
                    'key' => $key,
                    'value' => $this->domain->normalizeOptionalText(
                        isset($payload['value']) ? (string) $payload['value'] : null,
                    ),
                    'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                    'row_version' => 1,
                ]);

                return Result::success($record);
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.settings.create'],
            ));
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $payload): Result {
                $existing = $this->settings->findById($id);
                if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant setting not found.'));
                }

                $tenantId = (int) $existing->require('tenant_id');
                $key = $this->domain->normalizeKey((string) ($payload['key'] ?? $existing->require('key')));
                $byKey = $this->settings->findByTenantAndKey($tenantId, $key);
                if ($byKey !== null && (string) $byKey->id() !== (string) $existing->id()) {
                    return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant setting key already exists.'));
                }

                $groupId = array_key_exists('group_id', $payload)
                    ? (int) $payload['group_id']
                    : (int) $existing->require('group_id');
                $group = $this->groups->findById($groupId);
                if ($groupId < 1 || $group === null || (int) $group->require('tenant_id') !== $tenantId) {
                    return Result::failure(
                        new Error(TenantErrorCode::CONFLICT, 'Setting group must belong to same tenant.'),
                    );
                }

                $record = $this->settings->update($id, [
                    'group_id' => $groupId,
                    'key' => $key,
                    'value' => array_key_exists('value', $payload)
                        ? $this->domain->normalizeOptionalText(
                            isset($payload['value']) ? (string) $payload['value'] : null,
                        )
                        : $existing->get('value'),
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
                ['operation' => 'tenant.settings.update', 'setting_id' => (string) $id],
            ));
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id): Result {
                $existing = $this->settings->findById($id);
                if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant setting not found.'));
                }

                return Result::success($this->settings->delete($id));
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.settings.delete', 'setting_id' => (string) $id],
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
