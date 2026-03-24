<?php

namespace Modules\Core\Infrastructure\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileStorageService implements FileStorageServiceInterface
{
    /**
     * @var FilesystemAdapter The current disk adapter.
     */
    protected FilesystemAdapter $disk;

    /**
     * @var string The default disk name.
     */
    protected string $defaultDisk;

    /**
     * @param string $disk The default disk name.
     */
    public function __construct(string $disk = 'public')
    {
        $this->defaultDisk = $disk;
        $this->disk = Storage::disk($disk);
    }

    /**
     * {@inheritdoc}
     */
    public function store(string $tmpPath, string $directory, string $filename, ?string $disk = null): string
    {
        $adapter = $this->getDisk($disk);
        return $adapter->putFileAs($directory, $tmpPath, $filename);
    }

    /**
     * {@inheritdoc}
     */
    public function storeFile(UploadedFile $file, string $directory, ?string $filename = null, ?string $disk = null): string
    {
        $adapter = $this->getDisk($disk);
        return $adapter->putFileAs($directory, $file, $filename ?? $file->getClientOriginalName());
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $path, ?string $disk = null): bool
    {
        $adapter = $this->getDisk($disk);
        return $adapter->delete($path);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $path, ?string $disk = null): bool
    {
        $adapter = $this->getDisk($disk);
        return $adapter->exists($path);
    }

    /**
     * {@inheritdoc}
     */
    public function url(string $path, ?string $disk = null): string
    {
        $adapter = $this->getDisk($disk);
        return $adapter->url($path);
    }

    /**
     * {@inheritdoc}
     */
    public function size(string $path, ?string $disk = null): int
    {
        $adapter = $this->getDisk($disk);
        return $adapter->size($path);
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(string $path, ?string $disk = null): string|false
    {
        $adapter = $this->getDisk($disk);
        return $adapter->mimeType($path);
    }

    /**
     * {@inheritdoc}
     */
    public function lastModified(string $path, ?string $disk = null): int
    {
        $adapter = $this->getDisk($disk);
        return $adapter->lastModified($path);
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $path, ?string $disk = null): ?string
    {
        $adapter = $this->getDisk($disk);
        return $adapter->get($path);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $path, string $contents, ?string $disk = null): bool
    {
        $adapter = $this->getDisk($disk);
        return $adapter->put($path, $contents);
    }

    /**
     * {@inheritdoc}
     */
    public function copy(string $from, string $to, ?string $disk = null): bool
    {
        $adapter = $this->getDisk($disk);
        return $adapter->copy($from, $to);
    }

    /**
     * {@inheritdoc}
     */
    public function move(string $from, string $to, ?string $disk = null): bool
    {
        $adapter = $this->getDisk($disk);
        return $adapter->move($from, $to);
    }

    /**
     * {@inheritdoc}
     */
    public function temporaryUrl(string $path, int $minutes = 5, ?string $disk = null): ?string
    {
        $adapter = $this->getDisk($disk);
        // Not all disks support temporary URLs (e.g., public disk). Return null if method missing.
        if (method_exists($adapter, 'temporaryUrl')) {
            return $adapter->temporaryUrl($path, now()->addMinutes($minutes));
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function stream(string $path, ?string $disk = null): StreamedResponse
    {
        $adapter = $this->getDisk($disk);
        return $adapter->response($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDisk(): string
    {
        return $this->defaultDisk;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultDisk(string $disk): self
    {
        $this->defaultDisk = $disk;
        $this->disk = Storage::disk($disk);
        return $this;
    }

    /**
     * Get the disk adapter for the given disk name, falling back to the default.
     *
     * @param string|null $disk
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    protected function getDisk(?string $disk = null): FilesystemAdapter
    {
        if ($disk === null || $disk === $this->defaultDisk) {
            return $this->disk;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $adapter */
        $adapter = Storage::disk($disk);
        return $adapter;
    }
}
