<?php

namespace Modules\Core\Application\Contracts;

use Illuminate\Http\UploadedFile;

interface FileStorageServiceInterface
{
    /**
     * Store a file from a temporary path.
     *
     * @param string $tmpPath
     * @param string $directory
     * @param string $filename
     * @param string|null $disk
     * @return string The stored file path.
     */
    public function store(string $tmpPath, string $directory, string $filename, ?string $disk = null): string;

    /**
     * Store an uploaded file.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @param string|null $filename
     * @param string|null $disk
     * @return string The stored file path.
     */
    public function storeFile(UploadedFile $file, string $directory, ?string $filename = null, ?string $disk = null): string;

    /**
     * Delete a file.
     *
     * @param string $path
     * @param string|null $disk
     * @return bool
     */
    public function delete(string $path, ?string $disk = null): bool;

    /**
     * Check if a file exists.
     *
     * @param string $path
     * @param string|null $disk
     * @return bool
     */
    public function exists(string $path, ?string $disk = null): bool;

    /**
     * Get the public URL of a file.
     *
     * @param string $path
     * @param string|null $disk
     * @return string
     */
    public function url(string $path, ?string $disk = null): string;

    /**
     * Get the file size in bytes.
     *
     * @param string $path
     * @param string|null $disk
     * @return int
     */
    public function size(string $path, ?string $disk = null): int;

    /**
     * Get the file's MIME type.
     *
     * @param string $path
     * @param string|null $disk
     * @return string|false
     */
    public function mimeType(string $path, ?string $disk = null): string|false;

    /**
     * Get the last modification timestamp.
     *
     * @param string $path
     * @param string|null $disk
     * @return int
     */
    public function lastModified(string $path, ?string $disk = null): int;

    /**
     * Read the entire contents of a file.
     *
     * @param string $path
     * @param string|null $disk
     * @return string|null
     */
    public function read(string $path, ?string $disk = null): ?string;

    /**
     * Write contents to a file.
     *
     * @param string $path
     * @param string $contents
     * @param string|null $disk
     * @return bool
     */
    public function write(string $path, string $contents, ?string $disk = null): bool;

    /**
     * Copy a file to a new location.
     *
     * @param string $from
     * @param string $to
     * @param string|null $disk
     * @return bool
     */
    public function copy(string $from, string $to, ?string $disk = null): bool;

    /**
     * Move a file to a new location.
     *
     * @param string $from
     * @param string $to
     * @param string|null $disk
     * @return bool
     */
    public function move(string $from, string $to, ?string $disk = null): bool;

    /**
     * Get a temporary URL (for private files) valid for a given minutes.
     *
     * @param string $path
     * @param int $minutes
     * @param string|null $disk
     * @return string|null
     */
    public function temporaryUrl(string $path, int $minutes = 5, ?string $disk = null): ?string;

    /**
     * Stream a file to the response.
     *
     * @param string $path
     * @param string|null $disk
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream(string $path, ?string $disk = null);

    /**
     * Get the default disk name.
     *
     * @return string
     */
    public function getDefaultDisk(): string;

    /**
     * Set the default disk.
     *
     * @param string $disk
     * @return $this
     */
    public function setDefaultDisk(string $disk): self;
}
