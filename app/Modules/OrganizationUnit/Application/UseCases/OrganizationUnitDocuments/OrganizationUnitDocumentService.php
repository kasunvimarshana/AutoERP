<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\UseCases\OrganizationUnitDocuments;

use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Contracts\UuidGeneratorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\OrganizationUnit\Application\Contracts\UseCases\OrganizationUnitDocuments\OrganizationUnitDocumentServiceInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitDocumentRepositoryInterface;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Domain\Constants\OrganizationUnitErrorCode;
use Modules\OrganizationUnit\Domain\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Throwable;

final class OrganizationUnitDocumentService implements OrganizationUnitDocumentServiceInterface
{
    public function __construct(
        private readonly OrganizationUnitDocumentRepositoryInterface $documents,
        private readonly OrganizationUnitRepositoryInterface $units,
        private readonly TenantRepositoryInterface $tenants,
        private readonly OrganizationUnitDomainServiceInterface $domain,
        private readonly FileStorageServiceInterface $files,
        private readonly UuidGeneratorInterface $uuid,
    ) {
    }

    public function listByTenant(int|string $tenantId): Result
    {
        try {
            return Result::success($this->documents->listByTenant($this->domain->ensureTenantId($tenantId)));
        } catch (Throwable $exception) {
            return Result::failure(new Error(OrganizationUnitErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->documents->findById($id);
            if ($record === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit document not found.'));
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

            $name = $this->domain->normalizeName((string) ($payload['name'] ?? ''));
            if ($this->documents->findByTenantAndOrganizationUnitAndName($tenantId, $organizationUnitId, $name) !== null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::CONFLICT, 'Document name already exists for organization unit.'));
            }

            $record = $this->documents->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'name' => $name,
                'file_path' => $this->resolveFilePath($payload, $tenantId, $organizationUnitId),
                'mime_type' => $this->domain->normalizeOptionalText(isset($payload['mime_type']) ? (string) $payload['mime_type'] : null, 255),
                'size' => isset($payload['size']) ? (int) $payload['size'] : null,
                'type' => $this->domain->normalizeOptionalText(isset($payload['type']) ? (string) $payload['type'] : null, 255),
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
            $existing = $this->documents->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit document not found.'));
            }

            $tenantId = (int) $existing->require('tenant_id');
            $organizationUnitId = (int) $existing->require('organization_unit_id');

            if (array_key_exists('tenant_id', $payload) && (int) $payload['tenant_id'] !== $tenantId) {
                return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Document tenant cannot be changed.'));
            }

            if (array_key_exists('organization_unit_id', $payload)
                && (int) $payload['organization_unit_id'] !== $organizationUnitId) {
                return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_MISMATCH, 'Document organization unit cannot be changed.'));
            }

            $name = $this->domain->normalizeName((string) ($payload['name'] ?? $existing->require('name')));
            $byName = $this->documents->findByTenantAndOrganizationUnitAndName($tenantId, $organizationUnitId, $name);
            if ($byName !== null && (string) $byName->id() !== (string) $existing->id()) {
                return Result::failure(new Error(OrganizationUnitErrorCode::CONFLICT, 'Document name already exists for organization unit.'));
            }

            $filePath = array_key_exists('file_path', $payload) || array_key_exists('file_tmp_path', $payload)
                ? $this->resolveFilePath($payload, $tenantId, $organizationUnitId)
                : (string) $existing->require('file_path');

            $record = $this->documents->update($id, [
                'name' => $name,
                'file_path' => $filePath,
                'mime_type' => array_key_exists('mime_type', $payload)
                    ? $this->domain->normalizeOptionalText(isset($payload['mime_type']) ? (string) $payload['mime_type'] : null, 255)
                    : $existing->get('mime_type'),
                'size' => array_key_exists('size', $payload)
                    ? (isset($payload['size']) ? (int) $payload['size'] : null)
                    : $existing->get('size'),
                'type' => array_key_exists('type', $payload)
                    ? $this->domain->normalizeOptionalText(isset($payload['type']) ? (string) $payload['type'] : null, 255)
                    : $existing->get('type'),
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
            if ($this->documents->findById($id) === null) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit document not found.'));
            }

            return Result::success($this->documents->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(OrganizationUnitErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveFilePath(array $payload, int $tenantId, int $organizationUnitId): string
    {
        if (isset($payload['file_tmp_path'])) {
            $tmpPath = (string) $payload['file_tmp_path'];
            $originalName = isset($payload['file_original_name']) ? (string) $payload['file_original_name'] : 'document.bin';
            $extension = strtolower(trim((string) pathinfo($originalName, PATHINFO_EXTENSION)));
            $filename = sprintf(
                '%s-%s-%s.%s',
                $tenantId,
                $organizationUnitId,
                $this->uuid->generate(),
                $extension === '' ? 'bin' : $extension,
            );

            return $this->files->store($tmpPath, 'organization-units/documents', $filename);
        }

        $filePath = $this->domain->normalizeOptionalText(isset($payload['file_path']) ? (string) $payload['file_path'] : null, 2048);
        if ($filePath === null) {
            throw new \InvalidArgumentException('File path is required.');
        }

        return $filePath;
    }
}