<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

/**
 * Contract for querying warehouses.
 *
 * Separates read operations from write concerns, adhering to the Interface
 * Segregation and Single Responsibility principles. Controllers must depend
 * on this interface rather than on the repository abstraction directly.
 * The additional getByLocation() method handles filtering specific to
 * warehouse data.
 */
interface FindWarehouseServiceInterface extends ReadServiceInterface
{
    /**
     * Return all warehouses associated with a given location.
     *
     * @return array<int, \Modules\Warehouse\Domain\Entities\Warehouse>
     */
    public function getByLocation(int $locationId): array;
}
