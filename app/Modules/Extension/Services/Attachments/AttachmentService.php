<?php

declare(strict_types=1);

namespace Modules\Extension\Services\Attachments;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Modules\Audit\DTOs\AuditLogEntityChangeData;
use Modules\Audit\Services\AuditLogs\LogEntityChangeService;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Extension\Constants\ExtensionDefaults;
use Modules\Extension\Constants\ExtensionErrorCode;
use Modules\Extension\DTOs\AttachmentTarget;
use Modules\Extension\Enums\AttachmentPreviewStatus;
use Modules\Extension\Enums\AttachmentVisibility;
use Modules\Extension\Models\AttachmentModel;
use Modules\Extension\Repositories\AttachmentRepositoryInterface;
use RuntimeException;
use Throwable;

final class AttachmentService
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $attachments,
        private readonly AttachmentTargetResolver $targets,
        private readonly FileStorageServiceInterface $files,
        private readonly UuidGeneratorInterface $uuid,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly LogEntityChangeService $audit,
    ) {}

    public function list(array $filters, int $perPage = 0, int $page = 0): Result
    {
        try {
            [$tenantId, $organizationUnitId, $userId] = $this->scope();
            $resolvedPage = $page > 0 ? $page : ExtensionDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('extension.pagination.max_per_page', ExtensionDefaults::MAX_PER_PAGE))
                : (int) config('extension.pagination.default_per_page', ExtensionDefaults::DEFAULT_PER_PAGE);

            return Result::success($this->attachments->paginateAccessible(
                $tenantId,
                $organizationUnitId,
                $userId,
                $filters,
                $resolvedPerPage,
                $resolvedPage,
            ));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function get(int|string $id): Result
    {
        try {
            [$tenantId, $organizationUnitId, $userId] = $this->scope();
            $attachment = $this->attachments->findAccessible($id, $tenantId, $organizationUnitId, $userId);

            return $attachment instanceof AttachmentModel
                ? Result::success($attachment)
                : Result::failure(new Error(ExtensionErrorCode::NOT_FOUND, 'Attachment not found.'));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function create(array $payload, UploadedFile $file): Result
    {
        $stored = null;
        $disk = $this->storageDisk();

        try {
            [$tenantId, $organizationUnitId, $userId] = $this->scope();
            $target = $this->targets->resolve(
                (string) ($payload['attachable_type'] ?? ''),
                (int) ($payload['attachable_id'] ?? 0),
                $tenantId,
                $organizationUnitId,
            );
            $this->assertAllowedMimeType($file);

            $uuid = $this->uuid->generate();
            $storedFile = $this->storeFile($file, $target, $uuid, $disk);
            $stored = $storedFile['path'];

            $attachment = $this->attachments->transaction(function () use (
                $payload,
                $target,
                $userId,
                $uuid,
                $disk,
                $storedFile,
            ): AttachmentModel {
                return $this->attachments->create($this->attributesForFile(
                    payload: $payload,
                    target: $target,
                    userId: $userId,
                    uuid: $uuid,
                    versionGroupUuid: $uuid,
                    versionNumber: 1,
                    previousVersionId: null,
                    disk: $disk,
                    storedFile: $storedFile,
                ));
            });

            $this->logChange('attachment.created', $attachment, null, $this->auditValues($attachment));

            return Result::success($attachment);
        } catch (Throwable $exception) {
            if ($stored !== null) {
                $this->files->delete($stored, $disk);
            }

            return $this->failure($exception);
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $attachment = $this->requireAccessible($id);
            $expectedVersion = (int) ($payload['row_version'] ?? 0);
            if ($expectedVersion !== (int) $attachment->row_version) {
                return Result::failure(new Error(
                    ExtensionErrorCode::CONFLICT,
                    'Attachment was changed by another request.',
                ));
            }

            $before = $this->auditValues($attachment);
            $attributes = [
                'row_version' => ((int) $attachment->row_version) + 1,
                'updated_by' => $this->requireUserId(),
            ];

            foreach ([
                'category',
                'display_name',
                'description',
                'document_number',
                'tags',
                'metadata',
                'issued_at',
                'expires_at',
            ] as $field) {
                if (array_key_exists($field, $payload)) {
                    $attributes[$field] = $payload[$field];
                }
            }

            $this->assertDateRange(
                $attributes['issued_at'] ?? $attachment->issued_at?->toDateString(),
                $attributes['expires_at'] ?? $attachment->expires_at?->toDateString(),
            );
            $updated = $this->attachments->update($attachment, $attributes);
            $this->logChange('attachment.updated', $updated, $before, $this->auditValues($updated));

            return Result::success($updated);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function createVersion(int|string $id, array $payload, UploadedFile $file): Result
    {
        $stored = null;
        $disk = $this->storageDisk();

        try {
            $previous = $this->requireAccessible($id);
            $this->assertAllowedMimeType($file);
            $target = $this->targets->resolve(
                (string) $previous->attachable_type,
                (int) $previous->attachable_id,
                (int) $previous->tenant_id,
                $this->currentOrganizationUnit->currentOrganizationUnitId(),
            );
            $userId = $this->requireUserId();
            $uuid = $this->uuid->generate();
            $storedFile = $this->storeFile($file, $target, $uuid, $disk);
            $stored = $storedFile['path'];
            $versionGroupUuid = (string) ($previous->version_group_uuid ?: $previous->uuid);

            $attachment = $this->attachments->transaction(function () use (
                $payload,
                $previous,
                $target,
                $userId,
                $uuid,
                $disk,
                $storedFile,
                $versionGroupUuid,
            ): AttachmentModel {
                $versionNumber = $this->attachments->nextVersionNumber(
                    (int) $previous->tenant_id,
                    $versionGroupUuid,
                );
                $this->attachments->markVersionGroupNotCurrent(
                    (int) $previous->tenant_id,
                    $versionGroupUuid,
                    $userId,
                );

                $mergedPayload = [
                    'category' => $payload['category'] ?? $previous->category,
                    'visibility' => $previous->visibility instanceof AttachmentVisibility
                        ? $previous->visibility->value
                        : $previous->visibility,
                    'display_name' => $payload['display_name'] ?? $previous->display_name,
                    'description' => $payload['description'] ?? $previous->description,
                    'document_number' => $payload['document_number'] ?? $previous->document_number,
                    'tags' => $payload['tags'] ?? $previous->tags,
                    'metadata' => $payload['metadata'] ?? $previous->metadata,
                    'issued_at' => $payload['issued_at'] ?? $previous->issued_at?->toDateString(),
                    'expires_at' => $payload['expires_at'] ?? $previous->expires_at?->toDateString(),
                ];
                $this->assertDateRange($mergedPayload['issued_at'], $mergedPayload['expires_at']);

                return $this->attachments->create($this->attributesForFile(
                    payload: $mergedPayload,
                    target: $target,
                    userId: $userId,
                    uuid: $uuid,
                    versionGroupUuid: $versionGroupUuid,
                    versionNumber: $versionNumber,
                    previousVersionId: (int) $previous->getKey(),
                    disk: $disk,
                    storedFile: $storedFile,
                ));
            });

            $this->logChange('attachment.version_created', $attachment, null, $this->auditValues($attachment));

            return Result::success($attachment);
        } catch (Throwable $exception) {
            if ($stored !== null) {
                $this->files->delete($stored, $disk);
            }

            return $this->failure($exception);
        }
    }

    public function versions(int|string $id): Result
    {
        try {
            $attachment = $this->requireAccessible($id);
            [$tenantId, $organizationUnitId, $userId] = $this->scope();

            return Result::success($this->attachments->versionsAccessible(
                (string) $attachment->version_group_uuid,
                $tenantId,
                $organizationUnitId,
                $userId,
            ));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            $attachment = $this->requireAccessible($id);
            $before = $this->auditValues($attachment);
            $attachment->deleted_by = $this->requireUserId();
            $attachment->save();
            $this->attachments->delete($attachment);
            $this->logChange('attachment.deleted', $attachment, $before, null);

            return Result::success(null);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    private function requireAccessible(int|string $id): AttachmentModel
    {
        [$tenantId, $organizationUnitId, $userId] = $this->scope();
        $attachment = $this->attachments->findAccessible($id, $tenantId, $organizationUnitId, $userId);
        if (! $attachment instanceof AttachmentModel) {
            throw new InvalidArgumentException('Attachment not found.');
        }

        return $attachment;
    }

    /**
     * @return array{path: string, original_name: string, stored_name: string, mime_type: string, extension: string, size: int, checksum: string, preview_status: string}
     */
    private function storeFile(
        UploadedFile $file,
        AttachmentTarget $target,
        string $uuid,
        string $disk,
    ): array {
        $extension = strtolower((string) ($file->guessExtension() ?: 'bin'));
        $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'bin';
        $storedName = $uuid.'.'.$extension;
        $directory = sprintf(
            'documents/%d/%s/%s/%s',
            $target->tenantId,
            $target->organizationUnitId === null ? 'tenant' : 'org-'.$target->organizationUnitId,
            $target->alias,
            now()->format('Y/m'),
        );
        $path = $this->files->storeUploadedFile($file, $directory, $storedName, $disk);
        $mimeType = $this->files->mimeType($path, $disk);
        if (! is_string($mimeType) || $mimeType === '') {
            $mimeType = (string) ($file->getMimeType() ?: 'application/octet-stream');
        }

        $realPath = $file->getRealPath();
        $checksum = $realPath === false ? false : hash_file('sha256', $realPath);
        if (! is_string($checksum)) {
            $this->files->delete($path, $disk);
            throw new RuntimeException('Unable to calculate attachment checksum.');
        }

        return [
            'path' => $path,
            'original_name' => $this->normalizeOriginalName($file->getClientOriginalName()),
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $this->files->size($path, $disk),
            'checksum' => $checksum,
            'preview_status' => $this->previewStatus($mimeType)->value,
        ];
    }

    /**
     * @param array{path: string, original_name: string, stored_name: string, mime_type: string, extension: string, size: int, checksum: string, preview_status: string} $storedFile
     * @return array<string, mixed>
     */
    private function attributesForFile(
        array $payload,
        AttachmentTarget $target,
        int $userId,
        string $uuid,
        string $versionGroupUuid,
        int $versionNumber,
        ?int $previousVersionId,
        string $disk,
        array $storedFile,
    ): array {
        $visibility = AttachmentVisibility::from((string) ($payload['visibility'] ?? 'private'));
        $previewReady = $storedFile['preview_status'] === AttachmentPreviewStatus::Ready->value;

        return [
            'uuid' => $uuid,
            'version_group_uuid' => $versionGroupUuid,
            'previous_version_id' => $previousVersionId,
            'version_number' => $versionNumber,
            'is_current' => true,
            'row_version' => 1,
            'tenant_id' => $target->tenantId,
            'organization_unit_id' => $target->organizationUnitId,
            'metadata' => $payload['metadata'] ?? null,
            'attachable_type' => $target->alias,
            'attachable_id' => $target->model->getKey(),
            'source_module' => $target->module,
            'source_type' => $target->alias,
            'source_id' => $target->model->getKey(),
            'source_reference' => $target->reference,
            'source_context' => null,
            'category' => $payload['category'] ?? 'general',
            'visibility' => $visibility->value,
            'display_name' => $payload['display_name'] ?? $storedFile['original_name'],
            'original_file_name' => $storedFile['original_name'],
            'stored_file_name' => $storedFile['stored_name'],
            'file_name' => $storedFile['original_name'],
            'disk' => $disk,
            'file_path' => $storedFile['path'],
            'mime_type' => $storedFile['mime_type'],
            'extension' => $storedFile['extension'],
            'size' => $storedFile['size'],
            'checksum_sha256' => $storedFile['checksum'],
            'description' => $payload['description'] ?? null,
            'document_number' => $payload['document_number'] ?? null,
            'tags' => $payload['tags'] ?? null,
            'issued_at' => $payload['issued_at'] ?? null,
            'expires_at' => $payload['expires_at'] ?? null,
            'preview_status' => $storedFile['preview_status'],
            'preview_path' => $previewReady ? $storedFile['path'] : null,
            'thumbnail_path' => null,
            'uploaded_by' => $userId,
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
    }

    /** @return array{0: int, 1: ?int, 2: int} */
    private function scope(): array
    {
        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId === null || $tenantId < 1) {
            throw new InvalidArgumentException('A current tenant is required for attachment operations.');
        }

        return [
            $tenantId,
            $this->currentOrganizationUnit->currentOrganizationUnitId(),
            $this->requireUserId(),
        ];
    }

    private function requireUserId(): int
    {
        $userId = $this->currentUser->currentUserId();
        if ($userId === null || $userId < 1) {
            throw new InvalidArgumentException('A current user is required for attachment operations.');
        }

        return $userId;
    }

    private function storageDisk(): string
    {
        $disk = trim((string) config('extension.attachments.disk', $this->files->getDefaultDisk()));
        if ($disk === '') {
            throw new InvalidArgumentException('Attachment storage disk is not configured.');
        }

        return $disk;
    }

    private function assertAllowedMimeType(UploadedFile $file): void
    {
        $mimeType = (string) ($file->getMimeType() ?: '');
        $allowed = config('extension.attachments.allowed_mime_types', []);
        if (! is_array($allowed) || ! in_array($mimeType, $allowed, true)) {
            throw new InvalidArgumentException('The uploaded file type is not allowed.');
        }
    }

    private function previewStatus(string $mimeType): AttachmentPreviewStatus
    {
        $previewMimeTypes = config('extension.attachments.preview_mime_types', []);

        return is_array($previewMimeTypes) && in_array($mimeType, $previewMimeTypes, true)
            ? AttachmentPreviewStatus::Ready
            : AttachmentPreviewStatus::Unsupported;
    }

    private function assertDateRange(mixed $issuedAt, mixed $expiresAt): void
    {
        if (
            is_string($issuedAt)
            && $issuedAt !== ''
            && is_string($expiresAt)
            && $expiresAt !== ''
            && $issuedAt > $expiresAt
        ) {
            throw new InvalidArgumentException('Attachment issue date cannot be after its expiry date.');
        }
    }

    private function normalizeOriginalName(string $name): string
    {
        $normalized = basename(str_replace('\\', '/', $name));
        $normalized = preg_replace('/[\x00-\x1F\x7F]/u', '', $normalized) ?? '';
        $normalized = trim($normalized);

        return mb_substr($normalized === '' ? 'document.bin' : $normalized, 0, 255);
    }

    /** @return array<string, mixed> */
    private function auditValues(AttachmentModel $attachment): array
    {
        return [
            'id' => (int) $attachment->getKey(),
            'uuid' => $attachment->uuid,
            'attachable_type' => $attachment->attachable_type,
            'attachable_id' => (int) $attachment->attachable_id,
            'category' => $attachment->category,
            'visibility' => $attachment->visibility instanceof AttachmentVisibility
                ? $attachment->visibility->value
                : $attachment->visibility,
            'version_number' => (int) $attachment->version_number,
            'is_current' => (bool) $attachment->is_current,
            'mime_type' => $attachment->mime_type,
            'size_bytes' => (string) $attachment->size,
            'checksum_sha256' => $attachment->checksum_sha256,
        ];
    }

    private function logChange(
        string $event,
        AttachmentModel $attachment,
        ?array $before,
        ?array $after,
    ): void {
        $this->audit->execute(new AuditLogEntityChangeData(
            event: $event,
            entityType: 'attachment',
            entityId: (string) $attachment->getKey(),
            oldValues: $before,
            newValues: $after,
            tenantId: (int) $attachment->tenant_id,
            organizationUnitId: $attachment->organization_unit_id === null
                ? null
                : (int) $attachment->organization_unit_id,
            userId: $this->currentUser->currentUserId(),
            metadata: [
                'attachable_type' => $attachment->attachable_type,
                'attachable_id' => (int) $attachment->attachable_id,
            ],
            tags: ['attachment', 'document_management'],
        ));
    }

    private function failure(Throwable $exception): Result
    {
        $message = $exception->getMessage();
        $code = str_contains(strtolower($message), 'not found')
            ? ExtensionErrorCode::NOT_FOUND
            : ExtensionErrorCode::INVALID_VALUE;

        return Result::failure(new Error($code, $message));
    }
}
