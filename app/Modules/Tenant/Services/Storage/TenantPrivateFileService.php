<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Storage;

use InvalidArgumentException;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Core\Contracts\TenantPrivateFileServiceInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Tenant\Services\Documents\Scanning\TenantDocumentScannerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class TenantPrivateFileService implements TenantPrivateFileServiceInterface
{
    public function __construct(
        private readonly FileStorageServiceInterface $files,
        private readonly TenantDocumentScannerInterface $scanner,
        private readonly TenantStoragePathPolicy $paths,
        private readonly TenantStorageCleanupService $cleanup,
        private readonly UuidGeneratorInterface $uuid,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {}

    public function storeScannedFile(
        int $tenantId,
        string $relativeDirectory,
        string $temporaryPath,
        string $originalFilename,
        array $allowedMimeTypes,
        int $maxBytes,
    ): array {
        if ($tenantId < 1 || ! is_file($temporaryPath) || ! is_readable($temporaryPath)) {
            throw new InvalidArgumentException('A valid private file upload is required.');
        }
        if ($maxBytes < 1) {
            throw new InvalidArgumentException('A positive upload limit is required.');
        }

        $mimeType = mime_content_type($temporaryPath) ?: 'application/octet-stream';
        if (! in_array($mimeType, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException('Unsupported private file type.');
        }

        $size = filesize($temporaryPath);
        if ($size === false || $size < 1 || $size > $maxBytes) {
            throw new InvalidArgumentException('Private file size is invalid.');
        }

        $scan = $this->scanner->scan($temporaryPath);
        if (! $scan->clean) {
            $this->logger->warning('A tenant private file was rejected by security scanning.', [
                'tenant_id' => $tenantId,
                'scan_engine' => $scan->engine,
                'signature' => $scan->signature,
            ]);
            throw new InvalidArgumentException('The uploaded file failed security scanning.');
        }

        $checksum = hash_file('sha256', $temporaryPath);
        if (! is_string($checksum) || strlen($checksum) !== 64) {
            throw new RuntimeException('Private file checksum could not be calculated.');
        }

        $directory = $this->paths->canonicalize(
            $tenantId,
            sprintf('tenants/%d/%s', $tenantId, trim($relativeDirectory, '/')),
        );
        $storedPath = $this->files->store(
            $temporaryPath,
            $directory,
            $this->uuid->generate(),
            $this->disk(),
        );

        return [
            'object_key' => $this->paths->objectKeyFromPath($tenantId, $storedPath),
            'original_filename' => mb_substr(basename(trim($originalFilename)) ?: 'file.bin', 0, 255),
            'mime_type' => $mimeType,
            'size_bytes' => (int) $size,
            'checksum_sha256' => $checksum,
            'scan_engine' => $scan->engine,
            'scanned_at' => $this->clock->now(),
        ];
    }

    public function readStream(int $tenantId, string $objectKey)
    {
        $stream = $this->files->readStream(
            $this->paths->resolveObjectKey($tenantId, $objectKey),
            $this->disk(),
        );
        if (! is_resource($stream)) {
            throw new RuntimeException('Stored tenant private file could not be opened.');
        }

        return $stream;
    }

    public function scheduleCleanup(int $tenantId, ?string $objectKey, string $reason): void
    {
        if ($objectKey === null || trim($objectKey) === '') {
            return;
        }
        $this->cleanup->schedule(
            $tenantId,
            $this->paths->resolveObjectKey($tenantId, $objectKey),
            $reason,
        );
    }

    public function processCleanup(int $tenantId, ?string $objectKey): void
    {
        if ($objectKey === null || trim($objectKey) === '') {
            return;
        }
        try {
            $this->cleanup->processPath(
                $tenantId,
                $this->paths->resolveObjectKey($tenantId, $objectKey),
            );
        } catch (Throwable $exception) {
            $this->logger->warning('Immediate tenant private-file cleanup failed; durable cleanup remains queued.', [
                'tenant_id' => $tenantId,
                'object_key' => $objectKey,
                'exception' => $exception,
            ]);
        }
    }

    public function discardUnreferenced(int $tenantId, ?string $objectKey, string $reason): void
    {
        try {
            $this->scheduleCleanup($tenantId, $objectKey, $reason);
            $this->processCleanup($tenantId, $objectKey);
        } catch (Throwable $exception) {
            $this->logger->error('An unreferenced tenant private file could not be cleaned up.', [
                'tenant_id' => $tenantId,
                'object_key' => $objectKey,
                'exception' => $exception,
            ]);
        }
    }

    private function disk(): string
    {
        $disk = trim((string) config('tenant.documents.disk', 'tenant_private'));
        if ($disk === '') {
            throw new RuntimeException('Tenant private storage is not configured.');
        }

        return $disk;
    }
}
