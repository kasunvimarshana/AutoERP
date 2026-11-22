<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\OrganizationUnit\Contracts;

use Modules\OrganizationUnit\Data\OrganizationUnitBrandingProfile;

interface OrganizationUnitBrandingReaderInterface
{
    public function read(int $tenantId, int $organizationUnitId): ?OrganizationUnitBrandingProfile;
}
