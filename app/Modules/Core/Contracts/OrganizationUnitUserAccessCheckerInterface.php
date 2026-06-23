<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface OrganizationUnitUserAccessCheckerInterface
{
    /** @return list<int> */
    public function defaultOrganizationUnitIds(int $userId, int $tenantId): array;

    public function canAccessOrganizationUnit(int $userId, int $tenantId, int $organizationUnitId): bool;
}
