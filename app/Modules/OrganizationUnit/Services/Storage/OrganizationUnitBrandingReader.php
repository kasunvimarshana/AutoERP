<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Storage;

use Modules\PrivateObject\Contracts\PrivateObjectStorageInterface;
use Modules\OrganizationUnit\Contracts\OrganizationUnitBrandingReaderInterface;
use Modules\OrganizationUnit\Data\OrganizationUnitBrandingProfile;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Services\Storage\TenantStoragePathPolicy;

final class OrganizationUnitBrandingReader implements OrganizationUnitBrandingReaderInterface
{
    public function __construct(
        private readonly PrivateObjectStorageInterface $files,
        private readonly TenantStoragePathPolicy $tenantPaths,
    ) {}

    public function read(int $tenantId, int $organizationUnitId): ?OrganizationUnitBrandingProfile
    {
        if ($tenantId < 1 || $organizationUnitId < 1) {
            return null;
        }

        $organizationUnit = OrganizationUnitModel::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($organizationUnitId)
            ->first(['id', 'name', 'code', 'logo_object_key', 'logo_mime_type']);

        if (! $organizationUnit instanceof OrganizationUnitModel) {
            return null;
        }

        return new OrganizationUnitBrandingProfile(
            name: (string) $organizationUnit->getAttribute('name'),
            code: (string) $organizationUnit->getAttribute('code'),
            logoDataUri: $this->logoDataUri(
                $tenantId,
                $this->nullableString($organizationUnit->getAttribute('logo_object_key')),
                $this->nullableString($organizationUnit->getAttribute('logo_mime_type')),
            ),
        );
    }

    private function logoDataUri(int $tenantId, ?string $objectKey, ?string $mimeType): ?string
    {
        if ($objectKey === null || $mimeType === null || ! str_starts_with($mimeType, 'image/')) {
            return null;
        }

        $path = $this->tenantPaths->resolveObjectKey($tenantId, $objectKey);
        $disk = trim((string) config('organization-unit.storage.disk', 'tenant_private'));
        if ($disk === '' || ! $this->files->exists($path, $disk)) {
            return null;
        }

        $storedMimeType = $this->files->mimeType($path, $disk);
        if (! is_string($storedMimeType) || $storedMimeType !== $mimeType) {
            return null;
        }

        $stream = $this->files->readStream($path, $disk);
        if (! is_resource($stream)) {
            return null;
        }

        try {
            $content = stream_get_contents($stream);
        } finally {
            fclose($stream);
        }

        return is_string($content) && $content !== ''
            ? 'data:'.$mimeType.';base64,'.base64_encode($content)
            : null;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
