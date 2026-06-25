<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Documents;

use InvalidArgumentException;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantDocumentRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class TenantDocumentService
{
    public function __construct(
        private readonly TenantDocumentRepositoryInterface $documents,
        private readonly TenantDomainServiceInterface $rules,
        private readonly FileStorageServiceInterface $files,
        private readonly UuidGeneratorInterface $uuid,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $auditRecorder,
        private readonly ErrorNormalizerInterface $errors,
        private readonly LoggerInterface $logger,
    ) {}

    public function list(int $tenantId): Result
    {
        return Result::success($this->documents->listByTenant($tenantId));
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
                return Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'Tenant document name already exists.',
                ));
            }

            $storedFile = $this->storeUploadedFile($tenantId, $payload);
            $record = $this->documents->create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'document_type' => $this->rules->normalizeOptionalText(
                    isset($payload['document_type'])
                        ? (string) $payload['document_type']
                        : null,
                ),
                ...$storedFile,
                'metadata' => $this->rules->normalizeMetadata($payload['metadata'] ?? null),
                'row_version' => 1,
                'created_by' => $this->currentUser->currentUserId(),
                'updated_by' => $this->currentUser->currentUserId(),
            ]);

            $this->recordAudit('tenant.document.created', $record);

            return Result::success($record);
        } catch (Throwable $exception) {
            $this->removeStoredFile($storedFile);

            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::FILE_OPERATION_FAILED,
                ['operation' => 'tenant.document.create'],
            ));
        }
    }

    /** @param array<string, mixed> $payload */
    public function update(
        int $tenantId,
        int|string $id,
        array $payload,
    ): Result {
        $replacementFile = null;

        try {
            $existing = $this->documents->findByIdForTenant($id, $tenantId);
            if ($existing === null) {
                return Result::failure(new Error(
                    TenantErrorCode::NOT_FOUND,
                    'Tenant document not found.',
                ));
            }

            $expectedVersion = (int) ($payload['expected_version'] ?? 0);
            $name = array_key_exists('name', $payload)
                ? $this->rules->normalizeName((string) $payload['name'])
                : (string) $existing->require('name');

            $duplicate = $this->documents->findByTenantAndName($tenantId, $name);
            if ($duplicate !== null && (int) $duplicate->id() !== (int) $existing->id()) {
                return Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'Tenant document name already exists.',
                ));
            }

            $attributes = [
                'name' => $name,
                'document_type' => array_key_exists('document_type', $payload)
                    ? $this->rules->normalizeOptionalText(
                        $payload['document_type'] === null
                            ? null
                            : (string) $payload['document_type'],
                    )
                    : $existing->get('document_type'),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->rules->normalizeMetadata($payload['metadata'])
                    : $this->rules->normalizeMetadata($existing->get('metadata')),
                'updated_by' => $this->currentUser->currentUserId(),
            ];

            if (isset($payload['file_tmp_path'])) {
                $replacementFile = $this->storeUploadedFile($tenantId, $payload);
                $attributes = [...$attributes, ...$replacementFile];
            }

            $updated = $this->documents->updateWithVersion(
                $id,
                $tenantId,
                $expectedVersion,
                $attributes,
            );
            if ($updated === null) {
                $this->removeStoredFile($replacementFile);

                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Document changed since it was loaded. Refresh and try again.',
                ));
            }

            if ($replacementFile !== null) {
                $this->deleteFileIfPresent(
                    (string) $existing->require('storage_path'),
                    (string) $existing->require('storage_disk'),
                    'replacement cleanup',
                );
            }

            $this->recordAudit('tenant.document.updated', $updated);

            return Result::success($updated);
        } catch (Throwable $exception) {
            $this->removeStoredFile($replacementFile);

            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::FILE_OPERATION_FAILED,
                ['operation' => 'tenant.document.update'],
            ));
        }
    }

    public function delete(
        int $tenantId,
        int|string $id,
        int $expectedVersion,
    ): Result {
        $record = $this->documents->findByIdForTenant($id, $tenantId);
        if ($record === null) {
            return Result::failure(new Error(
                TenantErrorCode::NOT_FOUND,
                'Tenant document not found.',
            ));
        }

        if (! $this->documents->deleteWithVersion($id, $tenantId, $expectedVersion)) {
            return Result::failure(new Error(
                TenantErrorCode::VERSION_CONFLICT,
                'Document changed since it was loaded. Refresh and try again.',
            ));
        }

        $cleanupSucceeded = $this->deleteFileIfPresent(
            (string) $record->require('storage_path'),
            (string) $record->require('storage_disk'),
            'deletion cleanup',
        );

        $this->recordAudit(
            'tenant.document.deleted',
            $record,
            ['storage_cleanup_succeeded' => $cleanupSucceeded],
        );

        return Result::success(true);
    }

    public function download(int $tenantId, int|string $id): Result
    {
        try {
            $record = $this->documents->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(
                    TenantErrorCode::NOT_FOUND,
                    'Tenant document not found.',
                ));
            }

            $stream = $this->files->readStream(
                (string) $record->require('storage_path'),
                (string) $record->require('storage_disk'),
            );
            if (! is_resource($stream)) {
                throw new RuntimeException('Stored document could not be opened.');
            }

            return Result::success([
                'record' => $record,
                'stream' => $stream,
            ]);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::FILE_OPERATION_FAILED,
                ['operation' => 'tenant.document.download'],
            ));
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function storeUploadedFile(int $tenantId, array $payload): array
    {
        $temporaryPath = isset($payload['file_tmp_path'])
            ? (string) $payload['file_tmp_path']
            : '';
        if ($temporaryPath === '' || ! is_file($temporaryPath)) {
            throw new InvalidArgumentException('A valid uploaded file is required.');
        }

        $originalFilename = basename(
            (string) ($payload['file_original_name'] ?? 'document.bin'),
        );
        $mimeType = mime_content_type($temporaryPath) ?: 'application/octet-stream';
        $allowedMimeTypes = config('tenant.documents.allowed_mime_types', []);
        if (! is_array($allowedMimeTypes) || ! in_array($mimeType, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException('Unsupported document file type.');
        }

        $size = filesize($temporaryPath);
        $maxBytes = max(
            (int) config('tenant.documents.max_size_kb', 10240),
            1,
        ) * 1024;
        if ($size === false || $size < 1 || $size > $maxBytes) {
            throw new InvalidArgumentException('Document file size is invalid.');
        }

        $disk = $this->disk();
        $storedPath = $this->files->store(
            $temporaryPath,
            'tenants/'.$tenantId.'/documents',
            $this->uuid->generate(),
            $disk,
        );

        return [
            'storage_disk' => $disk,
            'storage_path' => $storedPath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size_bytes' => $size,
            'checksum_sha256' => hash_file('sha256', $temporaryPath),
        ];
    }

    /** @param array<string, mixed>|null $file */
    private function removeStoredFile(?array $file): void
    {
        if ($file === null) {
            return;
        }

        $path = (string) ($file['storage_path'] ?? '');
        $disk = (string) ($file['storage_disk'] ?? $this->disk());
        if ($path !== '') {
            $this->deleteFileIfPresent($path, $disk, 'failed operation cleanup');
        }
    }

    private function deleteFileIfPresent(
        string $path,
        string $disk,
        string $operation,
    ): bool {
        if (! $this->files->exists($path, $disk)) {
            return true;
        }

        if ($this->files->delete($path, $disk)) {
            return true;
        }

        $this->logger->warning('Tenant document storage cleanup failed.', [
            'operation' => $operation,
            'disk' => $disk,
            'path' => $path,
        ]);

        return false;
    }

    private function disk(): string
    {
        return (string) config('tenant.documents.disk', 'local');
    }

    /** @param array<string, mixed> $metadata */
    private function recordAudit(
        string $eventName,
        DataRecord $record,
        array $metadata = [],
    ): void {
        $this->auditRecorder->record(new AuditEventData(
            eventName: $eventName,
            eventCategory: 'administration',
            sourceModule: 'tenant',
            subjectType: 'tenant_document',
            subjectId: (string) $record->id(),
            subjectReference: (string) $record->get('name'),
            metadata: [
                'document_type' => $record->get('document_type'),
                'mime_type' => $record->get('mime_type'),
                'size_bytes' => $record->get('size_bytes'),
                ...$metadata,
            ],
            tags: ['tenant', 'document'],
        ));
    }
}
