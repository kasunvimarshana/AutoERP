<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

/**
 * Contract for querying organization units.
 *
 * Separates read operations from write (create/update/delete/move) concerns,
 * adhering to the Interface Segregation and Single Responsibility principles.
 * Controllers must depend on this interface rather than on the repository
 * abstraction directly. The additional getTree(), getDescendants(), and
 * getAncestors() methods handle hierarchical queries specific to
 * organization-unit data.
 */
interface FindOrganizationUnitServiceInterface extends ReadServiceInterface
{
    /**
     * Return the hierarchical tree of organization units for a given tenant.
     *
     * @return array<int|string, mixed>
     */
    public function getTree(int $tenantId, ?int $rootId = null): array;

    /**
     * Return all descendants (direct and indirect children) of a given unit.
     *
     * @return array<int, \Modules\OrganizationUnit\Domain\Entities\OrganizationUnit>
     */
    public function getDescendants(int $id): array;

    /**
     * Return all ancestors (parent chain) of a given unit.
     *
     * @return array<int, \Modules\OrganizationUnit\Domain\Entities\OrganizationUnit>
     */
    public function getAncestors(int $id): array;
}
