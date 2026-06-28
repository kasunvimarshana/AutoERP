<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Documents;

use Modules\Core\Tenancy\TenantPlanLimit;

use InvalidArgumentException;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\PrivateObject\Contracts\PrivateObjectStorageInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantDocumentRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantValueNormalizerInterface;
use Modules\Tenant\Services\Documents\Scanning\TenantDocumentScannerInterface;
use Modules\Tenant\Services\Storage\TenantStorageCleanupService;
use Modules\Tenant\Services\Storage\TenantStoragePathPolicy;
use Modules\Tenant\Services\TenantEntitlementService;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class TenantDocumentService
{
    public function __construct(
        private readonly TenantDocumentRepositoryInterface $documents,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantValueNormalizerInterface $rules,
        private readonly TenantEntitlementService $entitlements,
        private readonly TenantStorageCleanupService $storageCleanup,
        private readonly TenantStoragePathPolicy $storagePaths,
        private readonly TenantDocumentScannerInterface $scanner,
        private readonly PrivateObjectStorageInterface $files,
        private readonly UuidGeneratorInterface $uuid,
        private readonly ClockInterface $clock,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $auditRecorder,
        private readonly TransactionManagerInterface $transactions,
        private readonly LoggerInterface $logger,
    ) {}

    /** @param array<string, mixed> $filters */
    public function list(int $tenantId, array $filters): Result
    {
        $defaultPerPage = max(1, (int) config('tenant.pagination.default_per_page', 20));
        $maximumPerPage = max($defaultPerPage, (int) config('tenant.pagination.max_per_page', 100));

        return Result::success($this->documents->pageByTenant(
            $tenantId,
            $this->optionalText($filters['document_type'] ?? null),
            $this->optionalText($filters['search'] ?? null),
            max(1, min((int) ($filters['per_page'] ?? $defaultPerPage), $maximumPerPage)),
            max(1, (int) ($filters['page'] ?? 1)),
        ));
    }

    public function get(int $tenantId, int|string $id): Result
    {
        $record = $this->documents->findByIdForTenant($id, $tenantId);

        return $record === null
            ? Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant document not found.'))
            : Result::success($record);
    }

    /** @param array<string, mixed> $payload */
    public function create(int $tenantId, array $payload): Result
    {
        $storedFile = null;

        try {
            $name = $this->rules->normalizeName((string) ($payload['name'] ?? ''));
            if ($this->documents->findByTenantAndName($tenantId, $name) !== null) {
                return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant document name already exists.'));
            }

            $storedFile = $this->storeUploadedFile($tenantId, $payload);

            /** @var DataRecord $record */
            $record = $this->transactions->runInTransaction(function () use (
                $tenantId,
                $payload,
                $name,
                $storedFile,
            ): DataRecord {
                $this->lockTenant($tenantId);
                $this->assertStorageCapacity($tenantId, (int) $storedFile['size_bytes']);

                $record = $this->documents->create([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'document_type' => $this->normalizeDocumentType($payload['document_type'] ?? null),
                    ...$storedFile,
                    'row_version' => 1,
                    'created_by' => $this->currentUser->currentUserId(),
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);
                $this->recordAudit('tenant.document.created', $record);

                return $record;
            });

            return Result::success($record);
        } catch (InvalidArgumentException $exception) {
            $this->removeStoredFile($tenantId, $storedFile, 'failed document create cleanup');

            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        } catch (Throwable $exception) {
            $this->removeStoredFile($tenantId, $storedFile, 'failed document create cleanup');

            return $this->unexpectedFailure($exception, 'tenant.document.create', $tenantId);
        }
    }

    /** @param array<string, mixed> $payload */
    public function update(int $tenantId, int|string $id, array $payload): Result
    {
        $replacementFile = null;

        try {
            $existing = $this->documents->findByIdForTenant($id, $tenantId);
            if ($existing === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant document not found.'));
            }

            $expectedVersion = (int) ($payload['expected_version'] ?? 0);
            if ($expectedVersion < 1) {
                return Result::failure(new Error(TenantErrorCode::VERSION_CONFLICT, 'The current document version is required.'));
            }

            $name = array_key_exists('name', $payload)
                ? $this->rules->normalizeName((string) $payload['name'])
                : (string) $existing->require('name');
            $duplicate = $this->documents->findByTenantAndName($tenantId, $name);
            if ($duplicate !== null && (int) $duplicate->id() !== (int) $existing->id()) {
                return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Tenant document name already exists.'));
            }

            $attributes = [
                'name' => $name,
                'document_type' => array_key_exists('document_type', $payload)
                    ? $this->normalizeDocumentType($payload['document_type'])
                    : $existing->get('document_type'),
                'updated_by' => $this->currentUser->currentUserId(),
            ];

            if (isset($payload['file_tmp_path'])) {
                $replacementFile = $this->storeUploadedFile($tenantId, $payload);
                $attributes = [...$attributes, ...$replacementFile];
            }

            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use (
                $tenantId,
                $id,
                $expectedVersion,
                $existing,
                $attributes,
                $replacementFile,
            ): ?DataRecord {
                $this->lockTenant($tenantId);
                if ($replacementFile !== null) {
                    $this->assertStorageCapacity(
                        $tenantId,
                        (int) $replacementFile['size_bytes'],
                        (int) $existing->get('size_bytes', 0),
                    );
                }

                $updated = $this->documents->updateWithVersion($id, $tenantId, $expectedVersion, $attributes);
                if ($updated === null) {
                    return null;
                }

                if ($replacementFile !== null) {
                    $this->storageCleanup->schedule(
                        $tenantId,
                        $this->storagePaths->resolveObjectKey($tenantId, (string) $existing->require('object_key')),
                        'document replacement cleanup',
                    );
                }

                $this->recordAudit('tenant.document.updated', $updated);

                return $updated;
            });

            if ($updated === null) {
                $this->removeStoredFile($tenantId, $replacementFile, 'document version conflict cleanup');

                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Document changed since it was loaded. Refresh and try again.',
                ));
            }

            if ($replacementFile !== null) {
                $this->storageCleanup->processPath($tenantId, $this->storagePaths->resolveObjectKey($tenantId, (string) $existing->require('object_key')));
            }

            return Result::success($updated);
        } catch (InvalidArgumentException $exception) {
            $this->removeStoredFile($tenantId, $replacementFile, 'failed document update cleanup');

            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        } catch (Throwable $exception) {
            $this->removeStoredFile($tenantId, $replacementFile, 'failed document update cleanup');

            return $this->unexpectedFailure($exception, 'tenant.document.update', $tenantId, $id);
        }
    }

    public function delete(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        try {
            $record = $this->documents->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant document not found.'));
            }

            $deleted = $this->transactions->runInTransaction(function () use (
                $tenantId,
                $id,
                $expectedVersion,
                $record,
            ): bool {
                $this->lockTenant($tenantId);
                if (! $this->documents->deleteWithVersion($id, $tenantId, $expectedVersion)) {
                    return false;
                }

                $this->storageCleanup->schedule(
                    $tenantId,
                    $this->storagePaths->resolveObjectKey($tenantId, (string) $record->require('object_key')),
                    'document deletion cleanup',
                );
                $this->recordAudit('tenant.document.deleted', $record);

                return true;
            });

            if (! $deleted) {
                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Document changed since it was loaded. Refresh and try again.',
                ));
            }

            $this->storageCleanup->processPath($tenantId, $this->storagePaths->resolveObjectKey($tenantId, (string) $record->require('object_key')));

            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->unexpectedFailure($exception, 'tenant.document.delete', $tenantId, $id);
        }
    }

    public function download(int $tenantId, int|string $id): Result
    {
        try {
            $record = $this->documents->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant document not found.'));
            }

            $path = $this->storagePaths->resolveObjectKey($tenantId, (string) $record->require('object_key'));
            $stream = $this->files->readStream($path, $this->disk());
            if (! is_resource($stream)) {
                throw new RuntimeException('Stored tenant document could not be opened.');
            }

            return Result::success(['record' => $record, 'stream' => $stream]);
        } catch (Throwable $exception) {
            return $this->unexpectedFailure($exception, 'tenant.document.download', $tenantId, $id);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{object_key:string,original_filename:string,mime_type:string,size_bytes:int,checksum_sha256:string,scan_engine:string,scanned_at:\DateTimeInterface}
     */
    private function storeUploadedFile(int $tenantId, array $payload): array
    {
        $temporaryPath = isset($payload['file_tmp_path']) ? (string) $payload['file_tmp_path'] : '';
        if ($temporaryPath === '' || ! is_file($temporaryPath) || ! is_readable($temporaryPath)) {
            throw new InvalidArgumentException('A valid uploaded file is required.');
        }

        $originalFilename = mb_substr(basename((string) ($payload['file_original_name'] ?? 'document.bin')), 0, 255);
        $mimeType = mime_content_type($temporaryPath) ?: 'application/octet-stream';
        $allowedMimeTypes = config('tenant.documents.allowed_mime_types', []);
        if (! is_array($allowedMimeTypes) || ! in_array($mimeType, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException('Unsupported document file type.');
        }

        $size = filesize($temporaryPath);
        $maxBytes = max((int) config('tenant.documents.max_size_kb', 10240), 1) * 1024;
        if ($size === false || $size < 1 || $size > $maxBytes) {
            throw new InvalidArgumentException('Document file size is invalid.');
        }

        $checksum = hash_file('sha256', $temporaryPath);
        if (! is_string($checksum) || strlen($checksum) !== 64) {
            throw new RuntimeException('Document checksum could not be calculated.');
        }

        $scan = $this->scanner->scan($temporaryPath);
        if (! $scan->clean) {
            $this->logger->warning('A tenant document upload was rejected by security scanning.', [
                'tenant_id' => $tenantId,
                'checksum_sha256' => $checksum,
                'scan_engine' => $scan->engine,
                'signature' => $scan->signature,
            ]);
            throw new InvalidArgumentException('The uploaded document failed security scanning.');
        }

        $storedPath = $this->files->store(
            $temporaryPath,
            $this->storagePaths->documentDirectory($tenantId),
            $this->uuid->generate(),
            $this->disk(),
        );
        $objectKey = $this->storagePaths->objectKeyFromPath($tenantId, $storedPath);

        return [
            'object_key' => $objectKey,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size_bytes' => (int) $size,
            'checksum_sha256' => $checksum,
            'scan_engine' => $scan->engine,
            'scanned_at' => $this->clock->now(),
        ];
    }

    private function lockTenant(int $tenantId): void
    {
        if ($this->tenants->lockById($tenantId) === null) {
            throw new InvalidArgumentException('Tenant not found.');
        }
    }

    private function assertStorageCapacity(int $tenantId, int $newSize, int $replacedSize = 0): void
    {
        $limitMb = $this->entitlements->limit($tenantId, TenantPlanLimit::STORAGE_MEGABYTES);
        if ($limitMb === null) {
            return;
        }

        $currentBytes = $this->documents->totalSizeByTenant($tenantId);
        $projectedBytes = max(0, $currentBytes - max(0, $replacedSize)) + max(0, $newSize);
        $limitBytes = $limitMb * 1024 * 1024;
        if ($projectedBytes > $limitBytes) {
            throw new InvalidArgumentException(sprintf(
                'Tenant document storage limit of %d MB would be exceeded.',
                $limitMb,
            ));
        }
    }

    private function normalizeDocumentType(mixed $value): ?string
    {
        $normalized = $this->optionalText($value);

        return $normalized === null ? null : mb_substr($normalized, 0, 100);
    }

    private function optionalText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    /** @param array<string, mixed>|null $file */
    private function removeStoredFile(int $tenantId, ?array $file, string $reason): void
    {
        if ($file === null) {
            return;
        }

        $objectKey = trim((string) ($file['object_key'] ?? ''));
        $path = $objectKey === '' ? '' : $this->storagePaths->resolveObjectKey($tenantId, $objectKey);
        if ($path === '') {
            return;
        }

        try {
            $this->storageCleanup->schedule($tenantId, $path, $reason);
            $this->storageCleanup->processPath($tenantId, $path);
        } catch (Throwable $exception) {
            $this->logger->critical('Orphaned tenant file cleanup could not be persisted.', [
                'tenant_id' => $tenantId,
                'storage_path' => $path,
                'reason' => $reason,
                'exception' => $exception,
            ]);
        }
    }

    private function disk(): string
    {
        $disk = trim((string) config('tenant.documents.disk', 'tenant_private'));
        if ($disk === '') {
            throw new RuntimeException('Tenant private storage is not configured.');
        }

        return $disk;
    }

    private function recordAudit(string $eventName, DataRecord $record): void
    {
        $this->auditRecorder->record(new AuditEventData(
            eventName: $eventName,
            eventCategory: AuditEventCategory::ADMINISTRATION,
            sourceModule: 'tenant',
            subjectType: 'tenant_document',
            subjectId: (string) $record->id(),
            subjectReference: (string) $record->get('name'),
            metadata: [
                'document_type' => $record->get('document_type'),
                'mime_type' => $record->get('mime_type'),
                'size_bytes' => $record->get('size_bytes'),
                'scan_engine' => $record->get('scan_engine'),
            ],
            tags: ['tenant', 'document'],
        ));
    }

    private function unexpectedFailure(
        Throwable $exception,
        string $operation,
        int $tenantId,
        int|string|null $documentId = null,
    ): Result {
        $this->logger->error('Tenant document operation failed.', [
            'operation' => $operation,
            'tenant_id' => $tenantId,
            'tenant_document_id' => $documentId,
            'exception' => $exception,
        ]);

        return Result::failure(new Error(
            TenantErrorCode::FILE_OPERATION_FAILED,
            'The tenant document operation could not be completed.',
            ['operation' => $operation],
        ));
    }
}
