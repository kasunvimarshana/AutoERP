<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;

interface OrganizationUnitRepositoryInterface extends RepositoryInterface
{
    public function save(OrganizationUnit $organizationUnit): OrganizationUnit;

    public function findByCode(int $tenantId, string $code): ?OrganizationUnit;

    /**
     * Get direct children of an organization unit.
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getChildren(int $organizationUnitId): Collection;

    /**
     * Get all descendants (children, grandchildren, etc.) of an organization unit.
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getDescendants(int $organizationUnitId): Collection;

    /**
     * Get all ancestors (parent, grandparent, etc.) of an organization unit.
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getAncestors(int $organizationUnitId): Collection;

    /**
     * Get siblings of an organization unit.
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getSiblings(int $organizationUnitId): Collection;

    /**
     * Get organization units by type at a specific depth level.
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getByTypeAndLevel(int $tenantId, int $typeId, int $level): Collection;

    /**
     * Get root organization units for a tenant.
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getRoots(int $tenantId): Collection;

    /**
     * Get the full hierarchy tree for a tenant.
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getHierarchy(int $tenantId): Collection;
}
