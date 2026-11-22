<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Tenant\Services\Contracts;

interface TenantBrandingAssetReaderInterface
{
    public function logoDataUri(
        int $tenantId,
        ?string $objectKey,
        ?string $expectedMimeType = null,
    ): ?string;
}
