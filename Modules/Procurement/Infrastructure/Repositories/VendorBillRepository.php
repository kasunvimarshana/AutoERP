<?php

declare(strict_types=1);

namespace Modules\Procurement\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Procurement\Domain\Contracts\VendorBillRepositoryContract;
use Modules\Procurement\Domain\Entities\VendorBill;

/**
 * VendorBill repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant via HasTenant global scope.
 */
class VendorBillRepository extends AbstractRepository implements VendorBillRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = VendorBill::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByVendor(int $vendorId): Collection
    {
        return $this->query()->where('vendor_id', $vendorId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findByPurchaseOrder(int $purchaseOrderId): Collection
    {
        return $this->query()->where('purchase_order_id', $purchaseOrderId)->get();
    }
}
