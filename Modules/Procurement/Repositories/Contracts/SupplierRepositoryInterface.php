<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Procurement\Entities\Supplier;

/**
 * Supplier Repository Interface
 *
 * Defines the contract for supplier data access operations.
 */
interface SupplierRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find supplier by code.
     */
    public function findByCode(string $code): ?Supplier;

    /**
     * Get suppliers by status.
     */
    public function getSuppliersByStatus(string $status): Collection;

    /**
     * Get suppliers by rating.
     */
    public function getSuppliersByRating(float $minRating): Collection;

    /**
     * Search suppliers by name or code.
     */
    public function search(string $query): Collection;
}
