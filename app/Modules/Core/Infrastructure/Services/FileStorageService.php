<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileStorageService implements FileStorageServiceInterface
{
    protected FilesystemAdapter $disk;

    protected string $defaultDisk;

    public function __construct(string $defaultDisk)
    {
        $this->defaultDisk = $defaultDisk;
        $disk = Storage::disk($defaultDisk);
        if (! $disk instanceof FilesystemAdapter) {
            throw new \RuntimeException('Configured filesystem disk does not provide a FilesystemAdapter.');
        }

        $this->disk = $disk;
    }

    public function store(string $tmpPath, string $directory, string $filename, ?string $disk = null): string
    {
        if (! is_file($tmpPath)) {
            return '';
        }

        $contents = file_get_contents($tmpPath);
        if ($contents === false) {
            return '';
        }

        $targetPath = trim($directory, '/');
        $targetPath = $targetPath === '' ? $filename : $targetPath . '/' . $filename;
        $adapter = $this->getDisk($disk);
        $stored = $adapter->put($targetPath, $contents);

        return $stored ? $targetPath : '';
    }

    public function storeContent(
        string $contents,
        string $directory,
        string $filename,
        ?string $disk = null,
    ): string {
        $adapter = $this->getDisk($disk);
        $targetPath = trim($directory, '/');
        $targetPath = $targetPath === '' ? $filename : $targetPath . '/' . $filename;
        $stored = $adapter->put($targetPath, $contents);

        return $stored ? $targetPath : '';
    }

    /**
     * Compatibility bridge for duplicated sample interfaces in workspace.
     */
    public function storeFile(
        UploadedFile $file,
        string $directory,
        ?string $filename = null,
        ?string $disk = null,
    ): string {
        $realPath = $file->getRealPath();
        if ($realPath === false || ! is_file($realPath)) {
            return '';
        }

        $contents = file_get_contents($realPath);
        if ($contents === false) {
            return '';
        }

        return $this->storeContent(
            $contents,
            $directory,
            $filename ?? $file->getClientOriginalName(),
            $disk,
        );
    }

    public function delete(string $path, ?string $disk = null): bool
    {
        $adapter = $this->getDisk($disk);

        return $adapter->delete($path);
    }

    public function exists(string $path, ?string $disk = null): bool
    {
        $adapter = $this->getDisk($disk);

        return $adapter->exists($path);
    }

    public function url(string $path, ?string $disk = null): string
    {
        $adapter = $this->getDisk($disk);

        return $adapter->url($path);
    }

    public function size(string $path, ?string $disk = null): int
    {
        $adapter = $this->getDisk($disk);

        return $adapter->size($path);
    }

    public function mimeType(string $path, ?string $disk = null): string|false
    {
        $adapter = $this->getDisk($disk);

        return $adapter->mimeType($path);
    }

    public function lastModified(string $path, ?string $disk = null): int
    {
        $adapter = $this->getDisk($disk);

        return $adapter->lastModified($path);
    }

    public function read(string $path, ?string $disk = null): ?string
    {
        $adapter = $this->getDisk($disk);

        return $adapter->get($path);
    }

    public function write(string $path, string $contents, ?string $disk = null): bool
    {
        $adapter = $this->getDisk($disk);

        return $adapter->put($path, $contents);
    }

    public function copy(string $from, string $to, ?string $disk = null): bool
    {
        $adapter = $this->getDisk($disk);

        return $adapter->copy($from, $to);
    }

    public function move(string $from, string $to, ?string $disk = null): bool
    {
        $adapter = $this->getDisk($disk);

        return $adapter->move($from, $to);
    }

    public function temporaryUrl(
        string $path,
        int $minutes,
        ?string $disk = null,
    ): ?string {
        $adapter = $this->getDisk($disk);
        if (method_exists($adapter, 'temporaryUrl')) {
            return $adapter->temporaryUrl($path, now()->addMinutes($minutes));
        }

        return null;
    }

    public function readStream(string $path, ?string $disk = null): mixed
    {
        $adapter = $this->getDisk($disk);

        return $adapter->readStream($path);
    }

    /**
     * Compatibility bridge for duplicated sample interfaces in workspace.
     */
    public function stream(string $path, ?string $disk = null): StreamedResponse
    {
        $adapter = $this->getDisk($disk);

        return $adapter->response($path);
    }

    public function getDefaultDisk(): string
    {
        return $this->defaultDisk;
    }

    public function setDefaultDisk(string $disk): void
    {
        $this->defaultDisk = $disk;
        $adapter = Storage::disk($disk);
        if (! $adapter instanceof FilesystemAdapter) {
            throw new \RuntimeException('Configured filesystem disk does not provide a FilesystemAdapter.');
        }

        $this->disk = $adapter;
    }

    protected function getDisk(?string $disk = null): FilesystemAdapter
    {
        if ($disk === null || $disk === $this->defaultDisk) {
            return $this->disk;
        }

        /** @var FilesystemAdapter $adapter */
        $adapter = Storage::disk($disk);

        return $adapter;
    }
}
