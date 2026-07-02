<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Storage;

use InvalidArgumentException;
use Modules\PrivateObject\Contracts\PrivateObjectStorageInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class TenantLogoStorageService
{
    public function __construct(
        private readonly PrivateObjectStorageInterface $files,
        private readonly TenantStoragePathPolicy $paths,
        private readonly TenantStorageCleanupService $cleanup,
        private readonly UuidGeneratorInterface $uuid,
        private readonly LoggerInterface $logger,
    ) {}

    /** @return array{object_key:string,mime_type:string,size_bytes:int} */
    public function store(int $tenantId, string $temporaryPath): array
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

        return [
            'object_key' => $this->paths->objectKeyFromPath($tenantId, $path),
            'mime_type' => $mimeType,
            'size_bytes' => (int) $size,
        ];
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
            $this->logger->warning('Immediate tenant logo cleanup attempt failed; durable cleanup remains queued.', [
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
