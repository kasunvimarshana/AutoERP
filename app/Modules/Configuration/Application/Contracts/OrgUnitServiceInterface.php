<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts;

use Modules\Configuration\Domain\Entities\OrgUnit;

interface OrgUnitServiceInterface
{
    public function getOrgUnit(string $tenantId, string $id): OrgUnit;

    public function createOrgUnit(string $tenantId, array $data): OrgUnit;

    public function updateOrgUnit(string $tenantId, string $id, array $data): OrgUnit;

    public function deleteOrgUnit(string $tenantId, string $id): void;

    /** @return OrgUnit[] */
    public function getAllOrgUnits(string $tenantId): array;

    /** @return array Nested tree structure */
    public function getTree(string $tenantId): array;

    /** @return OrgUnit[] */
    public function getDescendants(string $tenantId, string $id): array;

    /** @return OrgUnit[] */
    public function getAncestors(string $tenantId, string $id): array;

    public function moveOrgUnit(string $tenantId, string $id, ?string $newParentId): OrgUnit;
}
