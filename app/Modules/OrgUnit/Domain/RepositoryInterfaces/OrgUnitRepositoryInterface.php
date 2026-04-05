<?php
declare(strict_types=1);
namespace Modules\OrgUnit\Domain\RepositoryInterfaces;

use Modules\OrgUnit\Domain\Entities\OrgUnit;

interface OrgUnitRepositoryInterface
{
    public function findById(int $id): ?OrgUnit;

    /** Return all root units (parentId = null) for a tenant. */
    public function findRoots(int $tenantId): array;

    /** Return direct children of the given parent. */
    public function findChildren(int $tenantId, int $parentId): array;

    /** Return all descendants using the materialized path. */
    public function findDescendants(int $tenantId, string $path): array;

    /** Return all ancestors via IDs extracted from the path. */
    public function findAncestors(int $tenantId, string $path): array;

    public function findAllByTenant(int $tenantId): array;

    public function create(array $data): OrgUnit;

    public function update(int $id, array $data): ?OrgUnit;

    public function delete(int $id): bool;

    /** Update path + level for all descendants when a unit is moved. */
    public function updateDescendantPaths(
        string $oldPathPrefix,
        string $newPathPrefix,
        int    $levelDelta
    ): void;

    public function existsByCode(int $tenantId, string $code, ?int $excludeId = null): bool;
}
