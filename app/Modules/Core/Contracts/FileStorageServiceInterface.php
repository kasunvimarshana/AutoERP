<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface FileStorageServiceInterface
{
    /** @return string Stored relative path. */
    public function store(string $sourcePath, string $directory, string $filename, ?string $disk = null): string;

    public function delete(string $path, ?string $disk = null): bool;

    public function exists(string $path, ?string $disk = null): bool;

    public function mimeType(string $path, ?string $disk = null): string|false;

    /** @return resource */
    public function readStream(string $path, ?string $disk = null): mixed;
}
