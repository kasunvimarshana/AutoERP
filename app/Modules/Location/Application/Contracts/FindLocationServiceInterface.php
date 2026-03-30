<?php

declare(strict_types=1);

namespace Modules\Location\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

/**
 * Contract for querying locations.
 *
 * Separates read operations from write concerns, adhering to the Interface
 * Segregation and Single Responsibility principles. Controllers must depend
 * on this interface rather than on the repository abstraction directly.
 * The additional getTree(), getDescendants(), and getAncestors() methods
 * handle hierarchical queries specific to location data.
 */
interface FindLocationServiceInterface extends ReadServiceInterface
{
    /**
     * Return the hierarchical tree of locations for a given tenant.
     *
     * @return array<int|string, mixed>
     */
    public function getTree(int $tenantId, ?int $rootId = null): array;

    /**
     * Return all descendants (direct and indirect children) of a given location.
     *
     * @return array<int, \Modules\Location\Domain\Entities\Location>
     */
    public function getDescendants(int $id): array;

    /**
     * Return all ancestors (parent chain) of a given location.
     *
     * @return array<int, \Modules\Location\Domain\Entities\Location>
     */
    public function getAncestors(int $id): array;
}
