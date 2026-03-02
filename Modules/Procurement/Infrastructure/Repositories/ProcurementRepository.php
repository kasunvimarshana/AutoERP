<?php

declare(strict_types=1);

namespace Modules\Procurement\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Procurement\Domain\Contracts\ProcurementRepositoryContract;
use Modules\Procurement\Domain\Entities\PurchaseOrder;

/**
 * Procurement repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant via HasTenant global scope.
 */
class ProcurementRepository extends AbstractRepository implements ProcurementRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = PurchaseOrder::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByOrderNumber(string $orderNumber): ?Model
    {
        return $this->query()->where('order_number', $orderNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByVendor(int $vendorId): Collection
    {
        return $this->query()->where('vendor_id', $vendorId)->get();
    }
}
