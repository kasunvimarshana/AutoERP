<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Storage;

use Illuminate\Http\UploadedFile;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Product\Application\Contracts\ImageStorageStrategyInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Default image storage strategy that delegates to the core FileStorageService.
 *
 * Swap this binding in the service provider to use a different strategy
 * (e.g., CDN, S3-optimised, resizing, etc.) without touching application code.
 */
class DefaultImageStorageStrategy implements ImageStorageStrategyInterface
{
    public function __construct(
        private readonly FileStorageServiceInterface $fileStorage
    ) {}

    public function store(UploadedFile $file, int $productId): string
    {
        return $this->fileStorage->storeFile(
            $file,
            "products/{$productId}",
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

    public function stream(string $path): StreamedResponse
    {
        return $this->fileStorage->stream($path);
    }
}
