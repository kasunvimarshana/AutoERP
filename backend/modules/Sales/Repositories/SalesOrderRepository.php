<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Sales\Models\SalesOrder;

/**
 * Sales Order Repository
 */
class SalesOrderRepository extends BaseRepository
{
    protected function model(): string
    {
        return SalesOrder::class;
    }

    public function findByOrderNumber(string $orderNumber): ?SalesOrder
    {
        return $this->newQuery()->where('order_number', $orderNumber)->first();
    }
}
