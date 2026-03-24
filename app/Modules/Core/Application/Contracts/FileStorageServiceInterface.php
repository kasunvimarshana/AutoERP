<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface FileStorageServiceInterface
{
    /**
     * Store a file from a temporary path.
     *
     * @return string The stored file path.
     */
    public function store(string $tmpPath, string $directory, string $filename, ?string $disk = null): string;

    /**
     * Store an uploaded file.
     *
     * @return string The stored file path.
     */
    public function storeFile(UploadedFile $file, string $directory, ?string $filename = null, ?string $disk = null): string;

    /**
     * Delete a file.
     */
    public function delete(string $path, ?string $disk = null): bool;

    /**
     * Check if a file exists.
     */
    public function exists(string $path, ?string $disk = null): bool;

    /**
     * Get the public URL of a file.
     */
    public function url(string $path, ?string $disk = null): string;

    /**
     * Get the file size in bytes.
     */
    public function size(string $path, ?string $disk = null): int;

    /**
     * Get the file's MIME type.
     */
    public function mimeType(string $path, ?string $disk = null): string|false;

    /**
     * Get the last modification timestamp.
     */
    public function lastModified(string $path, ?string $disk = null): int;

    /**
     * Read the entire contents of a file.
     */
    public function read(string $path, ?string $disk = null): ?string;

    /**
     * Write contents to a file.
     */
    public function write(string $path, string $contents, ?string $disk = null): bool;

    /**
     * Copy a file to a new location.
     */
    public function copy(string $from, string $to, ?string $disk = null): bool;

    /**
     * Move a file to a new location.
     */
    public function move(string $from, string $to, ?string $disk = null): bool;

    /**
     * Get a temporary URL (for private files) valid for a given minutes.
     */
    public function temporaryUrl(string $path, int $minutes = 5, ?string $disk = null): ?string;

    /**
     * Stream a file to the response.
     *
     * @return StreamedResponse
     */
    public function stream(string $path, ?string $disk = null);

    /**
     * Get the default disk name.
     */
    public function getDefaultDisk(): string;

    /**
     * Set the default disk.
     *
     * @return $this
     */
    public function setDefaultDisk(string $disk): self;
}
