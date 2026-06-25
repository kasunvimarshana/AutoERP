<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Contracts;

use Modules\OrganizationUnit\Data\OrganizationUnitBrandingProfile;

interface OrganizationUnitBrandingReaderInterface
{
    public function read(int $tenantId, int $organizationUnitId): ?OrganizationUnitBrandingProfile;
}
