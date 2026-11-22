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

interface OrganizationUnitOwnershipCheckerInterface
{
    public function belongsToTenant(int $organizationUnitId, int $tenantId): bool;

    public function belongsToActiveTenant(int $organizationUnitId, int $tenantId): bool;
}
