<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\UseCases\OrganizationUnitSettingGroups;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitSettingGroupRepositoryInterface;
use Modules\OrganizationUnit\Domain\Constants\OrganizationUnitErrorCode;
use Modules\OrganizationUnit\Domain\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Throwable;

final class OrganizationUnitSettingGroupService
{
    public function __construct(
        private readonly OrganizationUnitSettingGroupRepositoryInterface $groups,
        private readonly OrganizationUnitRepositoryInterface $units,
        private readonly TenantRepositoryInterface $tenants,
        private readonly OrganizationUnitDomainServiceInterface $domain,
    ) {}

    public function listByTenant(int|string $tenantId): Result
    {
        try {
            return Result::success($this->groups->listByTenant($this->domain->ensureTenantId($tenantId)));
        } catch (Throwable $exception) {
            return Result::failure(new Error(OrganizationUnitErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->groups->findById($id);
            if ($record === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit setting group not found.'));
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

            $key = $this->domain->normalizeKey((string) ($payload['key'] ?? ''));
            if ($this->groups->findByTenantAndOrganizationUnitAndKey($tenantId, $organizationUnitId, $key) !== null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::CONFLICT, 'Setting group key already exists for organization unit.'));
            }

            $parentId = isset($payload['parent_id']) ? (int) $payload['parent_id'] : null;
            if ($parentId !== null) {
                $parent = $this->groups->findById($parentId);
                if ($parent === null
                    || (int) $parent->require('tenant_id') !== $tenantId
                    || (int) $parent->require('organization_unit_id') !== $organizationUnitId) {
                    return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Parent group must belong to same tenant and organization unit.'));
                }
            }

            $record = $this->groups->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'key' => $key,
                'value' => $this->domain->normalizeOptionalText(isset($payload['value']) ? (string) $payload['value'] : null),
                'parent_id' => $parentId,
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
            $existing = $this->groups->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit setting group not found.'));
            }

            $tenantId = (int) $existing->require('tenant_id');
            $organizationUnitId = (int) $existing->require('organization_unit_id');

            if (array_key_exists('tenant_id', $payload) && (int) $payload['tenant_id'] !== $tenantId) {
                return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Setting group tenant cannot be changed.'));
            }

            if (array_key_exists('organization_unit_id', $payload)
                && (int) $payload['organization_unit_id'] !== $organizationUnitId) {
                return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Setting group organization unit cannot be changed.'));
            }

            $key = $this->domain->normalizeKey((string) ($payload['key'] ?? $existing->require('key')));
            $byKey = $this->groups->findByTenantAndOrganizationUnitAndKey($tenantId, $organizationUnitId, $key);
            if ($byKey !== null && (string) $byKey->id() !== (string) $existing->id()) {
                return Result::failure(new Error(OrganizationUnitErrorCode::CONFLICT, 'Setting group key already exists for organization unit.'));
            }

            $parentId = array_key_exists('parent_id', $payload)
                ? (isset($payload['parent_id']) ? (int) $payload['parent_id'] : null)
                : ($existing->get('parent_id') !== null ? (int) $existing->get('parent_id') : null);
            if ($parentId !== null) {
                if ((string) $parentId === (string) $id) {
                    return Result::failure(new Error(OrganizationUnitErrorCode::CONFLICT, 'Group cannot be its own parent.'));
                }

                $parent = $this->groups->findById($parentId);
                if ($parent === null
                    || (int) $parent->require('tenant_id') !== $tenantId
                    || (int) $parent->require('organization_unit_id') !== $organizationUnitId) {
                    return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Parent group must belong to same tenant and organization unit.'));
                }
            }

            $record = $this->groups->update($id, [
                'key' => $key,
                'value' => array_key_exists('value', $payload)
                    ? $this->domain->normalizeOptionalText(isset($payload['value']) ? (string) $payload['value'] : null)
                    : $existing->get('value'),
                'parent_id' => $parentId,
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
            if ($this->groups->findById($id) === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit setting group not found.'));
            }

            return Result::success($this->groups->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(OrganizationUnitErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
