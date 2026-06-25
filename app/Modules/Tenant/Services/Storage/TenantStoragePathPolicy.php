<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Storage;

use RuntimeException;

final class TenantStoragePathPolicy
{
    public function documentDirectory(int $tenantId): string
    {
        $this->assertTenantId($tenantId);

        return "tenants/{$tenantId}/documents";
    }

    public function brandingDirectory(int $tenantId): string
    {
        $this->assertTenantId($tenantId);

        return "tenants/{$tenantId}/branding";
    }


    public function objectKeyFromPath(int $tenantId, string $path): string
    {
        $canonicalPath = $this->canonicalize($tenantId, $path);
        $prefix = "tenants/{$tenantId}/";

        return $this->normalizeObjectKey(substr($canonicalPath, strlen($prefix)));
    }

    public function resolveObjectKey(int $tenantId, string $objectKey): string
    {
        $this->assertTenantId($tenantId);
        $objectKey = $this->normalizeObjectKey($objectKey);

        return $this->canonicalize($tenantId, "tenants/{$tenantId}/{$objectKey}");
    }

    public function canonicalize(int $tenantId, string $path): string
    {
        $this->assertTenantId($tenantId);
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        $segments = explode('/', $normalized);

        if (
            $normalized === ''
            || str_contains($normalized, "\0")
            || in_array('..', $segments, true)
            || in_array('.', $segments, true)
        ) {
            throw new RuntimeException('The tenant storage path is invalid.');
        }

        $prefix = "tenants/{$tenantId}/";
        if (! str_starts_with($normalized, $prefix) || $normalized === $prefix) {
            throw new RuntimeException('The storage path does not belong to the tenant.');
        }

        return $normalized;
    }


    private function normalizeObjectKey(string $objectKey): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($objectKey)), '/');
        $segments = explode('/', $normalized);

        if (
            $normalized === ''
            || str_contains($normalized, "\0")
            || str_starts_with($normalized, 'tenants/')
            || in_array('..', $segments, true)
            || in_array('.', $segments, true)
        ) {
            throw new RuntimeException('The tenant storage object key is invalid.');
        }

        return $normalized;
    }

    private function assertTenantId(int $tenantId): void
    {
        if ($tenantId < 1) {
            throw new RuntimeException('A valid tenant is required for storage operations.');
        }
    }
}
