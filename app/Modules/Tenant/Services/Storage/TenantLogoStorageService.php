<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Storage;

use InvalidArgumentException;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Tenant\Services\Documents\Scanning\TenantDocumentScannerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class TenantLogoStorageService
{
    public function __construct(
        private readonly FileStorageServiceInterface $files,
        private readonly TenantDocumentScannerInterface $scanner,
        private readonly TenantStoragePathPolicy $paths,
        private readonly TenantStorageCleanupService $cleanup,
        private readonly UuidGeneratorInterface $uuid,
        private readonly LoggerInterface $logger,
    ) {}

    public function store(int $tenantId, string $temporaryPath, string $originalFilename): string
    {
        if ($temporaryPath === '' || ! is_file($temporaryPath) || ! is_readable($temporaryPath)) {
            throw new InvalidArgumentException('A valid tenant logo is required.');
        }

        $mimeType = mime_content_type($temporaryPath) ?: 'application/octet-stream';
        $allowed = config('tenant.branding.allowed_logo_mime_types', []);
        if (! is_array($allowed) || ! in_array($mimeType, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported tenant logo file type.');
        }

        $size = filesize($temporaryPath);
        $maximumBytes = max((int) config('tenant.branding.max_logo_size_kb', 5120), 1) * 1024;
        if ($size === false || $size < 1 || $size > $maximumBytes) {
            throw new InvalidArgumentException('Tenant logo file size is invalid.');
        }

        $scan = $this->scanner->scan($temporaryPath);
        if (! $scan->clean) {
            $this->logger->warning('A tenant logo upload was rejected by security scanning.', [
                'tenant_id' => $tenantId,
                'scan_engine' => $scan->engine,
                'signature' => $scan->signature,
            ]);
            throw new InvalidArgumentException('The uploaded tenant logo failed security scanning.');
        }

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new InvalidArgumentException('Unsupported tenant logo file type.'),
        };

        $path = $this->files->store(
            $temporaryPath,
            $this->paths->brandingDirectory($tenantId),
            sprintf('logo-%s.%s', $this->uuid->generate(), $extension),
            $this->disk(),
        );

        return $this->paths->canonicalize($tenantId, $path);
    }

    public function scheduleCleanup(int $tenantId, ?string $path, string $reason): void
    {
        if ($path === null || trim($path) === '') {
            return;
        }

        $this->cleanup->schedule($tenantId, $path, $reason);
    }

    public function processCleanup(int $tenantId, ?string $path): void
    {
        if ($path === null || trim($path) === '') {
            return;
        }

        try {
            $this->cleanup->processPath($tenantId, $path);
        } catch (Throwable $exception) {
            $this->logger->warning('Immediate tenant logo cleanup attempt failed; durable cleanup remains queued.', [
                'tenant_id' => $tenantId,
                'storage_path' => $path,
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
