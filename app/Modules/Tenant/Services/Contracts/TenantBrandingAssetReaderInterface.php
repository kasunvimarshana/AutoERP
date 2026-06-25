<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

interface TenantBrandingAssetReaderInterface
{
    public function logoDataUri(
        int $tenantId,
        ?string $objectKey,
        ?string $expectedMimeType = null,
    ): ?string;
}
