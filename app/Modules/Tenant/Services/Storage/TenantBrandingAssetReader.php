<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Storage;

use Modules\PrivateObject\Contracts\PrivateObjectStorageInterface;
use Modules\Tenant\Services\Contracts\TenantBrandingAssetReaderInterface;
use Throwable;

final class TenantBrandingAssetReader implements TenantBrandingAssetReaderInterface
{
    public function __construct(
        private readonly PrivateObjectStorageInterface $files,
        private readonly TenantStoragePathPolicy $paths,
    ) {}

    public function logoDataUri(
        int $tenantId,
        ?string $objectKey,
        ?string $expectedMimeType = null,
    ): ?string {
        $objectKey = trim((string) $objectKey);
        if ($tenantId < 1 || $objectKey === '') {
            return null;
        }

        try {
            $path = $this->paths->resolveObjectKey($tenantId, $objectKey);
            $disk = $this->disk();
            if (! $this->files->exists($path, $disk)) {
                return null;
            }

            $mimeType = $this->files->mimeType($path, $disk);
            if (! is_string($mimeType)
                || ! in_array($mimeType, $this->allowedMimeTypes(), true)
                || ($expectedMimeType !== null && $expectedMimeType !== '' && $mimeType !== $expectedMimeType)
            ) {
                return null;
            }

            $stream = $this->files->readStream($path, $disk);
            try {
                $content = stream_get_contents($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return is_string($content)
                ? 'data:'.$mimeType.';base64,'.base64_encode($content)
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** @return list<string> */
    private function allowedMimeTypes(): array
    {
        $configured = config('tenant.branding.allowed_logo_mime_types', []);

        return is_array($configured)
            ? array_values(array_filter($configured, 'is_string'))
            : [];
    }

    private function disk(): string
    {
        return trim((string) config('tenant.documents.disk', 'tenant_private'));
    }
}
