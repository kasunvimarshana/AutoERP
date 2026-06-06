<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\OrganizationUnitSettings;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\OrganizationUnit\Constants\OrganizationUnitErrorCode;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Repositories\OrganizationUnitSettingGroupRepositoryInterface;
use Modules\OrganizationUnit\Repositories\OrganizationUnitSettingRepositoryInterface;
use Modules\OrganizationUnit\Services\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Throwable;

final class OrganizationUnitSettingService
{
    public function __construct(
        private readonly OrganizationUnitSettingRepositoryInterface $settings,
        private readonly OrganizationUnitSettingGroupRepositoryInterface $groups,
        private readonly OrganizationUnitRepositoryInterface $units,
        private readonly TenantRepositoryInterface $tenants,
        private readonly OrganizationUnitDomainServiceInterface $domain,
    ) {}

    public function listByTenant(int|string $tenantId): Result
    {
        try {
            return Result::success($this->settings->listByTenant($this->domain->ensureTenantId($tenantId)));
        } catch (Throwable $exception) {
            return Result::failure(new Error(OrganizationUnitErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->settings->findById($id);
            if ($record === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit setting not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(OrganizationUnitErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = $this->domain->ensureTenantId((int) ($payload['tenant_id'] ?? 0));
            if ($this->tenants->findById($tenantId) === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_NOT_FOUND, 'Tenant not found.'));
            }

            $organizationUnitId = (int) ($payload['organization_unit_id'] ?? 0);
            $unit = $this->units->findById($organizationUnitId);
            if ($organizationUnitId < 1 || $unit === null || (int) $unit->require('tenant_id') !== $tenantId) {
                return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Organization unit must belong to same tenant.'));
            }

            $groupId = (int) ($payload['group_id'] ?? 0);
            $group = $this->groups->findById($groupId);
            if ($groupId < 1
                || $group === null
                || (int) $group->require('tenant_id') !== $tenantId
                || (int) $group->require('organization_unit_id') !== $organizationUnitId) {
                return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Setting group must belong to same tenant and organization unit.'));
            }

            $key = $this->domain->normalizeKey((string) ($payload['key'] ?? ''));
            if ($this->settings->findByTenantAndOrganizationUnitAndGroupAndKey($tenantId, $organizationUnitId, $groupId, $key) !== null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::CONFLICT, 'Setting key already exists for group.'));
            }

            $record = $this->settings->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'group_id' => $groupId,
                'key' => $key,
                'value' => $this->domain->normalizeOptionalText(isset($payload['value']) ? (string) $payload['value'] : null),
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'row_version' => 1,
            ]);

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(OrganizationUnitErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->settings->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit setting not found.'));
            }

            $tenantId = (int) $existing->require('tenant_id');
            $organizationUnitId = (int) $existing->require('organization_unit_id');

            if (array_key_exists('tenant_id', $payload) && (int) $payload['tenant_id'] !== $tenantId) {
                return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Setting tenant cannot be changed.'));
            }

            if (array_key_exists('organization_unit_id', $payload)
                && (int) $payload['organization_unit_id'] !== $organizationUnitId) {
                return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Setting organization unit cannot be changed.'));
            }

            $groupId = array_key_exists('group_id', $payload) ? (int) $payload['group_id'] : (int) $existing->require('group_id');
            $group = $this->groups->findById($groupId);
            if ($groupId < 1
                || $group === null
                || (int) $group->require('tenant_id') !== $tenantId
                || (int) $group->require('organization_unit_id') !== $organizationUnitId) {
                return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Setting group must belong to same tenant and organization unit.'));
            }

            $key = $this->domain->normalizeKey((string) ($payload['key'] ?? $existing->require('key')));
            $byKey = $this->settings->findByTenantAndOrganizationUnitAndGroupAndKey($tenantId, $organizationUnitId, $groupId, $key);
            if ($byKey !== null && (string) $byKey->id() !== (string) $existing->id()) {
                return Result::failure(new Error(OrganizationUnitErrorCode::CONFLICT, 'Setting key already exists for group.'));
            }

            $record = $this->settings->update($id, [
                'group_id' => $groupId,
                'key' => $key,
                'value' => array_key_exists('value', $payload)
                    ? $this->domain->normalizeOptionalText(isset($payload['value']) ? (string) $payload['value'] : null)
                    : $existing->get('value'),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $this->domain->normalizeMetadata($existing->get('metadata')),
                'row_version' => ((int) $existing->get('row_version', 1)) + 1,
            ]);

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(OrganizationUnitErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            if ($this->settings->findById($id) === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit setting not found.'));
            }

            return Result::success($this->settings->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(OrganizationUnitErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
