<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Contracts;

interface OrganizationUnitOwnershipCheckerInterface
{
    public function belongsToActiveTenant(int $organizationUnitId, int $tenantId): bool;
}
