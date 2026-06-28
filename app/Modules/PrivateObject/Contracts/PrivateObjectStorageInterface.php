<?php

declare(strict_types=1);

namespace Modules\PrivateObject\Contracts;

interface PrivateObjectStorageInterface
{
    /** @return string Stored relative path. */
    public function store(string $sourcePath, string $directory, string $filename, ?string $disk = null): string;

    public function delete(string $path, ?string $disk = null): bool;

    public function exists(string $path, ?string $disk = null): bool;

    public function mimeType(string $path, ?string $disk = null): string|false;

    public function size(string $path, ?string $disk = null): int;

    /** @return list<string> */
    public function allFiles(string $directory, ?string $disk = null): array;

    /** @return resource */
    public function readStream(string $path, ?string $disk = null): mixed;
}
