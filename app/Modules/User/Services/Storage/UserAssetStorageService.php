<?php

declare(strict_types=1);

namespace Modules\User\Services\Storage;

use Modules\Core\Contracts\TenantPrivateFileServiceInterface;
use RuntimeException;

final class UserAssetStorageService
{
    public function __construct(private readonly TenantPrivateFileServiceInterface $files) {}

    /** @return array{object_key:string,original_filename:string,mime_type:string,size_bytes:int,checksum_sha256:string} */
    public function storeDocument(
        int $tenantId,
        int $userId,
        string $temporaryPath,
        string $originalFilename,
    ): array {
        if ($userId < 1) {
            throw new RuntimeException('A valid user is required for document storage.');
        }

        $allowedMimeTypes = config('user.storage.documents.allowed_mime_types', []);
        if (! is_array($allowedMimeTypes) || $allowedMimeTypes === []) {
            throw new RuntimeException('User document MIME types are not configured.');
        }
        $maxBytes = max((int) config('user.storage.documents.max_size_kb', 10240), 1) * 1024;

        return $this->files->storeFile(
            $tenantId,
            "users/{$userId}/documents",
            $temporaryPath,
            $originalFilename,
            array_values(array_filter($allowedMimeTypes, 'is_string')),
            $maxBytes,
        );
    }

    /** @return resource */
    public function read(int $tenantId, string $objectKey)
    {
        return $this->files->readStream($tenantId, $objectKey);
    }

    public function scheduleCleanup(int $tenantId, ?string $objectKey, string $reason): void
    {
        $this->files->scheduleCleanup($tenantId, $objectKey, $reason);
    }

    public function processCleanup(int $tenantId, ?string $objectKey): void
    {
        $this->files->processCleanup($tenantId, $objectKey);
    }

    public function discardUnreferenced(int $tenantId, ?string $objectKey, string $reason): void
    {
        $this->files->discardUnreferenced($tenantId, $objectKey, $reason);
    }
}
