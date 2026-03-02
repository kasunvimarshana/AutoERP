<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * VendorBill repository contract.
 *
 * Extends the base repository contract with vendor-bill-specific query methods.
 */
interface VendorBillRepositoryContract extends RepositoryContract
{
    /**
     * Find all vendor bills for a given vendor (tenant-scoped).
     */
    public function findByVendor(int $vendorId): Collection;

    /**
     * Find all vendor bills for a given purchase order (tenant-scoped).
     */
    public function findByPurchaseOrder(int $purchaseOrderId): Collection;
}
