<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Core\Contracts\FileStorageServiceInterface;
use RuntimeException;

final class FileStorageService implements FileStorageServiceInterface
{
    private readonly string $defaultDisk;

    private readonly FilesystemAdapter $defaultAdapter;

    public function __construct(string $defaultDisk)
    {
        $this->defaultDisk = $this->normalizeDisk($defaultDisk);
        $this->defaultAdapter = $this->resolveDisk($this->defaultDisk);
    }

    public function store(string $sourcePath, string $directory, string $filename, ?string $disk = null): string
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new InvalidArgumentException('Source file is not readable.');
        }

        $stream = fopen($sourcePath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Unable to open source file for reading.');
        }

        $targetPath = $this->buildTargetPath($directory, $filename);

        try {
            if (! $this->getDisk($disk)->writeStream($targetPath, $stream)) {
                throw new RuntimeException(sprintf('Unable to store file at path: %s', $targetPath));
            }
        } finally {
            fclose($stream);
        }

        return $targetPath;
    }

    public function delete(string $path, ?string $disk = null): bool
    {
        return $this->getDisk($disk)->delete($this->normalizeRelativePath($path));
    }

    public function exists(string $path, ?string $disk = null): bool
    {
        return $this->getDisk($disk)->exists($this->normalizeRelativePath($path));
    }

    public function mimeType(string $path, ?string $disk = null): string|false
    {
        return $this->getDisk($disk)->mimeType($this->normalizeRelativePath($path));
    }

    public function size(string $path, ?string $disk = null): int
    {
        return (int) $this->getDisk($disk)->size($this->normalizeRelativePath($path));
    }

    public function allFiles(string $directory, ?string $disk = null): array
    {
        $files = $this->getDisk($disk)->allFiles($this->normalizeRelativePath($directory));

        return array_values(array_map(
            fn (string $path): string => $this->normalizeRelativePath($path),
            $files,
        ));
    }

    public function readStream(string $path, ?string $disk = null): mixed
    {
        $normalizedPath = $this->normalizeRelativePath($path);
        $stream = $this->getDisk($disk)->readStream($normalizedPath);

        if (! is_resource($stream)) {
            throw new RuntimeException(sprintf('Unable to open stored file for reading: %s', $normalizedPath));
        }

        return $stream;
    }

    private function getDisk(?string $disk): FilesystemAdapter
    {
        if ($disk === null) {
            return $this->defaultAdapter;
        }

        $disk = $this->normalizeDisk($disk);

        return $disk === $this->defaultDisk ? $this->defaultAdapter : $this->resolveDisk($disk);
    }

    private function resolveDisk(string $disk): FilesystemAdapter
    {
        $adapter = Storage::disk($disk);
        if (! $adapter instanceof FilesystemAdapter) {
            throw new RuntimeException(sprintf('Storage disk "%s" is not a filesystem adapter.', $disk));
        }

        return $adapter;
    }

    private function normalizeDisk(string $disk): string
    {
        $disk = trim($disk);
        if ($disk === '') {
            throw new InvalidArgumentException('Storage disk cannot be empty.');
        }

        return $disk;
    }

    private function buildTargetPath(string $directory, string $filename): string
    {
        $filename = trim($filename);
        if (
            $filename === ''
            || $filename === '.'
            || $filename === '..'
            || str_contains($filename, '/')
            || str_contains($filename, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $filename) === 1
        ) {
            throw new InvalidArgumentException('Filename must be a safe basename.');
        }

        $directory = trim($directory);
        if ($directory === '') {
            return $filename;
        }

        return $this->normalizeRelativePath($directory).'/'.$filename;
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = trim($path, '/');

        if ($path === '' || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            throw new InvalidArgumentException('Storage path must be a non-empty relative path.');
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('Storage path contains an invalid segment.');
            }
        }

        return implode('/', $segments);
    }
}
