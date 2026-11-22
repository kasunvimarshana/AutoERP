<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Core\Contracts;

interface OrganizationUnitUserAccessCheckerInterface
{
    /** @return list<int> */
    public function defaultOrganizationUnitIds(int $userId, int $tenantId): array;

    /** @return list<int> */
    public function accessibleOrganizationUnitIds(int $userId, int $tenantId): array;

    public function canAccessOrganizationUnit(
        int $userId,
        int $tenantId,
        int $organizationUnitId,
        bool $lockForUpdate = false,
    ): bool;
}
