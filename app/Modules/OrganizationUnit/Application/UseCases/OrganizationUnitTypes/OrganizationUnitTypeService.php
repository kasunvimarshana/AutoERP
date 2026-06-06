<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\UseCases\OrganizationUnitTypes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitTypeRepositoryInterface;
use Modules\OrganizationUnit\Domain\Constants\OrganizationUnitErrorCode;
use Modules\OrganizationUnit\Domain\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Throwable;

final class OrganizationUnitTypeService
{
    public function __construct(
        private readonly OrganizationUnitTypeRepositoryInterface $types,
        private readonly TenantRepositoryInterface $tenants,
        private readonly OrganizationUnitDomainServiceInterface $domain,
    ) {}

    public function listByTenant(int|string $tenantId): Result
    {
        try {
            return Result::success($this->types->listByTenant($this->domain->ensureTenantId($tenantId)));
        } catch (Throwable $exception) {
            return Result::failure(new Error(OrganizationUnitErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->types->findById($id);
            if ($record === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit type not found.'));
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

            $name = $this->domain->normalizeName((string) ($payload['name'] ?? ''));
            if ($this->types->findByTenantAndName($tenantId, $name) !== null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::CONFLICT, 'Type name already exists for tenant.'));
            }

            $record = $this->types->create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'level' => $this->domain->normalizeLevel((int) ($payload['level'] ?? 0)),
                'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : true,
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
            $existing = $this->types->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit type not found.'));
            }

            $tenantId = (int) $existing->require('tenant_id');
            if (array_key_exists('tenant_id', $payload) && (int) $payload['tenant_id'] !== $tenantId) {
                return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Type tenant cannot be changed.'));
            }

            $name = $this->domain->normalizeName((string) ($payload['name'] ?? $existing->require('name')));
            $byName = $this->types->findByTenantAndName($tenantId, $name);
            if ($byName !== null && (string) $byName->id() !== (string) $existing->id()) {
                return Result::failure(new Error(OrganizationUnitErrorCode::CONFLICT, 'Type name already exists for tenant.'));
            }

            $record = $this->types->update($id, [
                'name' => $name,
                'level' => array_key_exists('level', $payload)
                    ? $this->domain->normalizeLevel((int) $payload['level'])
                    : (int) $existing->get('level', 0),
                'is_active' => array_key_exists('is_active', $payload)
                    ? (bool) $payload['is_active']
                    : (bool) $existing->get('is_active', true),
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
            $existing = $this->types->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit type not found.'));
            }

            $tenantId = (int) $existing->require('tenant_id');
            $deleted = $this->types->delete($id);

            if ($deleted) {
            }

            return Result::success($deleted);
        } catch (Throwable $exception) {
            return Result::failure(new Error(OrganizationUnitErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
