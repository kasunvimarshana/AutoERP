<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases\Settings;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Contracts\UseCases\Settings\TenantSettingServiceInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantSettingGroupRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantSettingRepositoryInterface;
use Modules\Tenant\Domain\Constants\TenantErrorCode;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface;
use Throwable;

final class TenantSettingService implements TenantSettingServiceInterface
{
    public function __construct(
        private readonly TenantSettingRepositoryInterface $settings,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantSettingGroupRepositoryInterface $groups,
        private readonly TenantDomainServiceInterface $domain,
    ) {
    }

    public function listByTenant(int|string $tenantId): Result
    {
        try {
            return Result::success($this->settings->listByTenant($tenantId));
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->settings->findById($id);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant setting not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if ($tenantId < 1 || $this->tenants->findById($tenantId) === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
            }

            $groupId = (int) ($payload['group_id'] ?? 0);
            $group = $this->groups->findById($groupId);
            if ($groupId < 1 || $group === null || (int) $group->require('tenant_id') !== $tenantId) {
                return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Setting group must belong to same tenant.'));
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
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->settings->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant setting not found.'));
            }

            $tenantId = (int) $existing->require('tenant_id');
            $key = $this->domain->normalizeKey((string) ($payload['key'] ?? $existing->require('key')));
            $byKey = $this->settings->findByTenantAndKey($tenantId, $key);
            if ($byKey !== null && (string) $byKey->id() !== (string) $existing->id()) {
                return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant setting key already exists.'));
            }

            $groupId = array_key_exists('group_id', $payload) ? (int) $payload['group_id'] : (int) $existing->require('group_id');
            $group = $this->groups->findById($groupId);
            if ($groupId < 1 || $group === null || (int) $group->require('tenant_id') !== $tenantId) {
                return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Setting group must belong to same tenant.'));
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
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            if ($this->settings->findById($id) === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant setting not found.'));
            }

            return Result::success($this->settings->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
