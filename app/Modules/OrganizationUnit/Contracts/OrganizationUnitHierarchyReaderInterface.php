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

interface OrganizationUnitHierarchyReaderInterface
{
    /**
     * Return active ancestor IDs from the nearest parent to the tenant root.
     *
     * @return list<int>
     */
    public function activeAncestorIds(int $tenantId, int $organizationUnitId): array;
}
