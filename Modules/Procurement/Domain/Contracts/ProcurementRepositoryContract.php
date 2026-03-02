<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Procurement repository contract.
 *
 * Extends the base repository contract with procurement-specific query methods.
 */
interface ProcurementRepositoryContract extends RepositoryContract
{
    /**
     * Find a purchase order by its order number (tenant-scoped).
     */
    public function findByOrderNumber(string $orderNumber): ?Model;

    /**
     * Find all purchase orders for a given vendor (tenant-scoped).
     */
    public function findByVendor(int $vendorId): Collection;
}
