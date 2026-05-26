<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases\Documents;

use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\Contracts\UuidGeneratorInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Contracts\UseCases\Documents\TenantDocumentServiceInterface;
use Modules\Tenant\Application\Repositories\TenantDocumentRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Application\Support\TenantContext;
use Modules\Tenant\Domain\Constants\TenantErrorCode;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface;
use Throwable;

final class TenantDocumentService implements TenantDocumentServiceInterface
{
    public function __construct(
        private readonly TenantDocumentRepositoryInterface $documents,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainServiceInterface $domain,
        private readonly FileStorageServiceInterface $files,
        private readonly UuidGeneratorInterface $uuid,
        private readonly TenantContext $tenantContext,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {
    }

    public function listByTenant(int|string $tenantId): Result
    {
        try {
            $resolvedTenantId = $this->tenantContext->resolveTenantId($this->toNullableInt($tenantId));

            return Result::success($this->documents->listByTenant($resolvedTenantId));
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.documents.list'],
            ));
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->documents->findById($id);
            if ($record === null || ! $this->isRecordInTenantScope($record)) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant document not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.documents.get', 'document_id' => (string) $id],
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

                $name = $this->domain->normalizeName((string) ($payload['name'] ?? ''));
                if ($this->documents->findByTenantAndName($tenantId, $name) !== null) {
                    return Result::failure(
                        new Error(TenantErrorCode::CONFLICT, 'Tenant document name already exists.'),
                    );
                }

                $filePath = $this->resolveFilePath($payload, $tenantId);

                $record = $this->documents->create([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'file_path' => $filePath,
                    'mime_type' => isset($payload['mime_type']) ? (string) $payload['mime_type'] : null,
                    'size' => isset($payload['size']) ? (int) $payload['size'] : null,
                    'type' => isset($payload['type']) ? (string) $payload['type'] : null,
                    'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                    'row_version' => 1,
                ]);

                return Result::success($record);
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.documents.create'],
            ));
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $payload): Result {
                $existing = $this->documents->findById($id);
                if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant document not found.'));
                }

                $tenantId = (int) $existing->require('tenant_id');
                $name = $this->domain->normalizeName((string) ($payload['name'] ?? $existing->require('name')));
                $byName = $this->documents->findByTenantAndName($tenantId, $name);
                if ($byName !== null && (string) $byName->id() !== (string) $existing->id()) {
                    return Result::failure(
                        new Error(TenantErrorCode::CONFLICT, 'Tenant document name already exists.'),
                    );
                }

                $filePath = array_key_exists('file_path', $payload) || isset($payload['file_tmp_path'])
                    ? $this->resolveFilePath($payload, $tenantId)
                    : (string) $existing->require('file_path');

                $record = $this->documents->update($id, [
                    'name' => $name,
                    'file_path' => $filePath,
                    'mime_type' => array_key_exists('mime_type', $payload)
                        ? (isset($payload['mime_type']) ? (string) $payload['mime_type'] : null)
                        : $existing->get('mime_type'),
                    'size' => array_key_exists('size', $payload)
                        ? (isset($payload['size']) ? (int) $payload['size'] : null)
                        : $existing->get('size'),
                    'type' => array_key_exists('type', $payload)
                        ? (isset($payload['type']) ? (string) $payload['type'] : null)
                        : $existing->get('type'),
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
                ['operation' => 'tenant.documents.update', 'document_id' => (string) $id],
            ));
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id): Result {
                $existing = $this->documents->findById($id);
                if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant document not found.'));
                }

                return Result::success($this->documents->delete($id));
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.documents.delete', 'document_id' => (string) $id],
            ));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveFilePath(array $payload, int $tenantId): string
    {
        if (isset($payload['file_tmp_path'])) {
            $tmpPath = (string) $payload['file_tmp_path'];
            $originalName = isset($payload['file_original_name'])
                ? (string) $payload['file_original_name']
                : 'document.bin';
            $extension = strtolower(trim((string) pathinfo($originalName, PATHINFO_EXTENSION)));
            $filename = sprintf(
                '%s-%s.%s',
                $tenantId,
                $this->uuid->generate(),
                $extension === '' ? 'bin' : $extension,
            );

            return $this->files->store($tmpPath, 'tenants/documents', $filename);
        }

        return $this->domain->normalizeOptionalText(
            isset($payload['file_path']) ? (string) $payload['file_path'] : null,
        )
            ?? throw new \InvalidArgumentException('File path is required.');
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
