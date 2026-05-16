<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pluggable attachment storage strategy for tenant attachments.
 *
 * Implementations may apply attachment-specific concerns such as virus scanning,
 * CDN proxying, or encryption before delegating to the underlying
 * file-storage infrastructure. Swap the binding in the service provider to
 * use a different strategy without touching application code.
 */
interface AttachmentStorageStrategyInterface
{
    /**
     * Store an uploaded file and return the stored path.
     */
    public function store(UploadedFile $file, int $tenantId): string;

    /**
     * Store a file from a temporary path and return the stored path.
     */
    public function storeFromPath(string $tmpPath, string $directory, string $filename): string;

    /**
     * Delete a stored file by its path.
     */
    public function delete(string $path): bool;

    /**
     * Stream a stored file as an HTTP response.
     *
     * Controllers must call this method through the strategy rather than
     * accessing the underlying storage layer directly, preserving loose
     * coupling and allowing implementations to add CDN redirection,
     * access-control checks, or caching headers transparently.
     */
    public function stream(string $path): StreamedResponse;
}
