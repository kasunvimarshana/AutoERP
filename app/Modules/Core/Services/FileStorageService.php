<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Core\Contracts\FileStorageServiceInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FileStorageService implements FileStorageServiceInterface
{
    protected FilesystemAdapter $disk;

    protected string $defaultDisk;

    public function __construct(string $defaultDisk)
    {
        if (trim($defaultDisk) === '') {
            throw new InvalidArgumentException('Default storage disk cannot be empty.');
        }

        $this->defaultDisk = $defaultDisk;
        $disk = Storage::disk($defaultDisk);
        if (! $disk instanceof FilesystemAdapter) {
            throw new RuntimeException('Configured filesystem disk does not provide a FilesystemAdapter.');
        }

        $this->disk = $disk;
    }

    public function store(string $tmpPath, string $directory, string $filename, ?string $disk = null): string
    {
        if (! is_file($tmpPath)) {
            throw new InvalidArgumentException(sprintf('Temporary file not found: %s', $tmpPath));
        }

        $contents = file_get_contents($tmpPath);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read temporary file: %s', $tmpPath));
        }

        return $this->storeContent($contents, $directory, $filename, $disk);
    }

    public function storeContent(
        string $contents,
        string $directory,
        string $filename,
        ?string $disk = null,
    ): string {
        if ($contents === '') {
            throw new InvalidArgumentException('File contents cannot be empty.');
        }

        $adapter = $this->getDisk($disk);
        $targetPath = $this->buildTargetPath($directory, $filename);
        $stored = $adapter->put($targetPath, $contents);
        if (! $stored) {
            throw new RuntimeException(sprintf('Unable to store file at path: %s', $targetPath));
        }

        return $targetPath;
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
            throw new RuntimeException(sprintf('Unable to read uploaded file: %s', $realPath));
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

        if (! $adapter->exists($path)) {
            return null;
        }

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
        if ($minutes < 1) {
            throw new InvalidArgumentException('Temporary URL minutes must be greater than zero.');
        }

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
        if (trim($disk) === '') {
            throw new InvalidArgumentException('Storage disk cannot be empty.');
        }

        $this->defaultDisk = $disk;
        $adapter = Storage::disk($disk);
        if (! $adapter instanceof FilesystemAdapter) {
            throw new RuntimeException('Configured filesystem disk does not provide a FilesystemAdapter.');
        }

        $this->disk = $adapter;
    }

    protected function getDisk(?string $disk = null): FilesystemAdapter
    {
        if ($disk !== null && trim($disk) === '') {
            throw new InvalidArgumentException('Storage disk cannot be empty.');
        }

        if ($disk === null || $disk === $this->defaultDisk) {
            return $this->disk;
        }

        /** @var FilesystemAdapter $adapter */
        $adapter = Storage::disk($disk);

        return $adapter;
    }

    private function buildTargetPath(string $directory, string $filename): string
    {
        $normalizedFilename = trim($filename);
        if ($normalizedFilename === '') {
            throw new InvalidArgumentException('Filename cannot be empty.');
        }

        $targetPath = trim($directory, '/');

        return $targetPath === '' ? $normalizedFilename : $targetPath.'/'.$normalizedFilename;
    }
}
