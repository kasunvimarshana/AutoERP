<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface FileStorageServiceInterface
{
    /** @return string Stored file path. */
    public function store(string $tmpPath, string $directory, string $filename, ?string $disk = null): string;

    /** @return string Stored file path. */
    public function storeContent(
        string $contents,
        string $directory,
        string $filename,
        ?string $disk = null,
    ): string;

    public function delete(string $path, ?string $disk = null): bool;

    public function exists(string $path, ?string $disk = null): bool;

    public function url(string $path, ?string $disk = null): string;

    public function size(string $path, ?string $disk = null): int;

    public function mimeType(string $path, ?string $disk = null): string|false;

    public function lastModified(string $path, ?string $disk = null): int;

    public function read(string $path, ?string $disk = null): ?string;

    public function write(string $path, string $contents, ?string $disk = null): bool;

    public function copy(string $from, string $to, ?string $disk = null): bool;

    public function move(string $from, string $to, ?string $disk = null): bool;

    public function temporaryUrl(string $path, int $minutes, ?string $disk = null): ?string;

    public function readStream(string $path, ?string $disk = null): mixed;

    public function getDefaultDisk(): string;

    public function setDefaultDisk(string $disk): void;
}
