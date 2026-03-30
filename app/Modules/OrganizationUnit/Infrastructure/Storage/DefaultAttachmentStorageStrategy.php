<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Storage;

use Illuminate\Http\UploadedFile;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\AttachmentStorageStrategyInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Default attachment storage strategy that delegates to the core FileStorageService.
 *
 * Swap this binding in the service provider to use a different strategy
 * (e.g., CDN, S3-optimised, virus-scanning, encryption, etc.) without
 * touching application code.
 */
class DefaultAttachmentStorageStrategy implements AttachmentStorageStrategyInterface
{
    public function __construct(
        private readonly FileStorageServiceInterface $fileStorage
    ) {}

    public function store(UploadedFile $file, int $orgUnitId): string
    {
        return $this->fileStorage->storeFile(
            $file,
            "org-units/{$orgUnitId}",
        );
    }

    public function storeFromPath(string $tmpPath, string $directory, string $filename): string
    {
        return $this->fileStorage->store($tmpPath, $directory, $filename);
    }

    public function delete(string $path): bool
    {
        return $this->fileStorage->delete($path);
    }

    public function url(string $path): string
    {
        return $this->fileStorage->url($path);
    }

    public function stream(string $path): StreamedResponse
    {
        return $this->fileStorage->stream($path);
    }
}
