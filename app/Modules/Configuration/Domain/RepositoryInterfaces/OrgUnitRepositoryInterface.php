<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\RepositoryInterfaces;

use Modules\Configuration\Domain\Entities\OrgUnit;

interface OrgUnitRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?OrgUnit;

    /** @return OrgUnit[] */
    public function findAll(string $tenantId): array;

    /** @return OrgUnit[] */
    public function findChildren(string $tenantId, string $parentId): array;

    /** @return OrgUnit[] */
    public function findDescendants(string $tenantId, string $path): array;

    public function save(OrgUnit $orgUnit): void;

    public function delete(string $tenantId, string $id): void;
}
