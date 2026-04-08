<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pluggable image storage strategy for product images.
 *
 * Implementations may apply image-specific concerns such as resizing,
 * thumbnail generation, CDN proxying, or watermarking before delegating
 * to the underlying file-storage infrastructure.
 */
interface ImageStorageStrategyInterface
{
    /**
     * Store an uploaded image file and return the stored path.
     */
    public function store(UploadedFile $file, int $productId): string;

    /**
     * Store an image from a temporary file path and return the stored path.
     */
    public function storeFromPath(string $tmpPath, string $directory, string $filename): string;

    /**
     * Delete a stored image by its path.
     */
    public function delete(string $path): bool;

    /**
     * Stream a stored image as an HTTP response.
     *
     * Controllers must call this method through the strategy rather than
     * accessing the underlying storage layer directly, preserving loose
     * coupling and allowing implementations to add CDN redirection,
     * access-control checks, or caching headers transparently.
     */
    public function stream(string $path): StreamedResponse;
}
