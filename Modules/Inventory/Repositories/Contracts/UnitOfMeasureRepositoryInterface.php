<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Inventory\Entities\UnitOfMeasure;

/**
 * Unit of Measure Repository Interface
 *
 * Defines the contract for UoM data access operations.
 */
interface UnitOfMeasureRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find UoM by code.
     */
    public function findByCode(string $code): ?UnitOfMeasure;

    /**
     * Get UoMs by category.
     */
    public function getByCategory(string $category): Collection;

    /**
     * Get active UoMs.
     */
    public function getActiveUoms(): Collection;

    /**
     * Get base units by category.
     */
    public function getBaseUnits(): Collection;
}
