<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Storage;

use InvalidArgumentException;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Tenant\Services\Documents\Scanning\TenantDocumentScannerInterface;
use Modules\Tenant\Services\Storage\TenantStorageCleanupService;
use Modules\Tenant\Services\Storage\TenantStoragePathPolicy;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class OrganizationUnitAssetStorageService
{
    public function __construct(
        private readonly FileStorageServiceInterface $files,
        private readonly TenantDocumentScannerInterface $scanner,
        private readonly TenantStoragePathPolicy $tenantPaths,
        private readonly TenantStorageCleanupService $cleanup,
        private readonly UuidGeneratorInterface $uuid,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {}

    /** @return array{object_key:string,mime_type:string,size_bytes:int} */
    public function storeLogo(int $tenantId, int $organizationUnitId, string $temporaryPath): array
    {
        $metadata = $this->inspect(
            $tenantId,
            $organizationUnitId,
            $temporaryPath,
            $this->configuredStringList('storage.logo.allowed_mime_types'),
            $this->maximumBytes('storage.logo.max_size_kb', 5120),
            'organization-unit logo',
        );

        $extension = match ($metadata['mime_type']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new InvalidArgumentException('Unsupported organization-unit logo file type.'),
        };

        $path = $this->files->store(
            $temporaryPath,
            $this->directory($tenantId, $organizationUnitId, 'branding'),
            sprintf('logo-%s.%s', $this->uuid->generate(), $extension),
            $this->disk(),
        );

        return [
            'object_key' => $this->tenantPaths->objectKeyFromPath($tenantId, $path),
            'mime_type' => $metadata['mime_type'],
            'size_bytes' => $metadata['size_bytes'],
        ];
    }

    /**
     * @return array{
     *   object_key:string,
     *   original_filename:string,
     *   mime_type:string,
     *   size_bytes:int,
     *   checksum_sha256:string,
     *   scan_engine:string,
     *   scanned_at:\DateTimeInterface
     * }
     */
    public function storeDocument(
        int $tenantId,
        int $organizationUnitId,
        string $temporaryPath,
        string $originalFilename,
    ): array {
        $metadata = $this->inspect(
            $tenantId,
            $organizationUnitId,
            $temporaryPath,
            $this->configuredStringList('storage.documents.allowed_mime_types'),
            $this->maximumBytes('storage.documents.max_size_kb', 10240),
            'organization-unit document',
        );

        $checksum = hash_file('sha256', $temporaryPath);
        if (! is_string($checksum) || strlen($checksum) !== 64) {
            throw new RuntimeException('Document checksum could not be calculated.');
        }

        $path = $this->files->store(
            $temporaryPath,
            $this->directory($tenantId, $organizationUnitId, 'documents'),
            $this->uuid->generate(),
            $this->disk(),
        );

        return [
            'object_key' => $this->tenantPaths->objectKeyFromPath($tenantId, $path),
            'original_filename' => mb_substr(basename(trim($originalFilename)) ?: 'document.bin', 0, 255),
            'mime_type' => $metadata['mime_type'],
            'size_bytes' => $metadata['size_bytes'],
            'checksum_sha256' => $checksum,
            'scan_engine' => $metadata['scan_engine'],
            'scanned_at' => $this->clock->now(),
        ];
    }

    public function scheduleCleanup(int $tenantId, ?string $objectKey, string $reason): void
    {
        if ($objectKey === null || trim($objectKey) === '') {
            return;
        }

        $this->cleanup->schedule(
            $tenantId,
            $this->tenantPaths->resolveObjectKey($tenantId, $objectKey),
            $reason,
        );
    }

    public function discardUnreferencedAsset(int $tenantId, ?string $objectKey, string $reason): void
    {
        if ($objectKey === null || trim($objectKey) === '') {
            return;
        }

        try {
            $this->scheduleCleanup($tenantId, $objectKey, $reason);
            $this->processCleanup($tenantId, $objectKey);
        } catch (Throwable $exception) {
            $this->logger->error('An unreferenced organization-unit asset could not be queued for cleanup.', [
                'tenant_id' => $tenantId,
                'object_key' => $objectKey,
                'exception' => $exception,
            ]);

            try {
                $path = $this->tenantPaths->resolveObjectKey($tenantId, $objectKey);
                if ($this->files->exists($path, $this->disk())) {
                    $this->files->delete($path, $this->disk());
                }
            } catch (Throwable $deleteException) {
                $this->logger->error('Direct cleanup of an unreferenced organization-unit asset also failed.', [
                    'tenant_id' => $tenantId,
                    'object_key' => $objectKey,
                    'exception' => $deleteException,
                ]);
            }
        }
    }

    public function processCleanup(int $tenantId, ?string $objectKey): void
    {
        if ($objectKey === null || trim($objectKey) === '') {
            return;
        }

        try {
            $this->cleanup->processPath(
                $tenantId,
                $this->tenantPaths->resolveObjectKey($tenantId, $objectKey),
            );
        } catch (Throwable $exception) {
            $this->logger->warning(
                'Immediate organization-unit asset cleanup failed; durable cleanup remains queued.',
                [
                    'tenant_id' => $tenantId,
                    'object_key' => $objectKey,
                    'exception' => $exception,
                ],
            );
        }
    }

    /** @return array{mime_type:string,size_bytes:int,scan_engine:string} */
    private function inspect(
        int $tenantId,
        int $organizationUnitId,
        string $temporaryPath,
        array $allowedMimeTypes,
        int $maximumBytes,
        string $assetLabel,
    ): array {
        if ($tenantId < 1 || $organizationUnitId < 1) {
            throw new InvalidArgumentException('Valid tenant and organization-unit ownership is required.');
        }
        if ($temporaryPath === '' || ! is_file($temporaryPath) || ! is_readable($temporaryPath)) {
            throw new InvalidArgumentException("A valid {$assetLabel} upload is required.");
        }

        $mimeType = mime_content_type($temporaryPath) ?: 'application/octet-stream';
        if (! in_array($mimeType, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException("Unsupported {$assetLabel} file type.");
        }

        $size = filesize($temporaryPath);
        if ($size === false || $size < 1 || $size > $maximumBytes) {
            throw new InvalidArgumentException(ucfirst($assetLabel).' file size is invalid.');
        }

        $scan = $this->scanner->scan($temporaryPath);
        if (! $scan->clean) {
            $this->logger->warning('An organization-unit asset upload was rejected by security scanning.', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'asset' => $assetLabel,
                'scan_engine' => $scan->engine,
                'signature' => $scan->signature,
            ]);
            throw new InvalidArgumentException('The uploaded file failed security scanning.');
        }

        return [
            'mime_type' => $mimeType,
            'size_bytes' => (int) $size,
            'scan_engine' => $scan->engine,
        ];
    }

    /** @return list<string> */
    private function configuredStringList(string $path): array
    {
        $values = config('organization-unit.'.$path, []);

        return is_array($values)
            ? array_values(array_filter($values, static fn (mixed $value): bool => is_string($value) && trim($value) !== ''))
            : [];
    }

    private function maximumBytes(string $path, int $defaultKilobytes): int
    {
        return max((int) config('organization-unit.'.$path, $defaultKilobytes), 1) * 1024;
    }

    private function directory(int $tenantId, int $organizationUnitId, string $category): string
    {
        return $this->tenantPaths->canonicalize(
            $tenantId,
            "tenants/{$tenantId}/organization-units/{$organizationUnitId}/{$category}",
        );
    }

    private function disk(): string
    {
        $disk = trim((string) config('organization-unit.storage.disk', config('tenant.documents.disk', 'tenant_private')));
        if ($disk === '') {
            throw new RuntimeException('Organization-unit private storage is not configured.');
        }

        return $disk;
    }
}
