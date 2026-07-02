<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface TenantPrivateFileServiceInterface
{
    /**
     * @param list<string> $allowedMimeTypes
     * @return array{object_key:string,original_filename:string,mime_type:string,size_bytes:int,checksum_sha256:string}
     */
    public function storeFile(
        int $tenantId,
        string $relativeDirectory,
        string $temporaryPath,
        string $originalFilename,
        array $allowedMimeTypes,
        int $maxBytes,
    ): array;

    /** @return resource */
    public function readStream(int $tenantId, string $objectKey);

    public function scheduleCleanup(int $tenantId, ?string $objectKey, string $reason): void;

    public function processCleanup(int $tenantId, ?string $objectKey): void;

    public function discardUnreferenced(int $tenantId, ?string $objectKey, string $reason): void;
}
