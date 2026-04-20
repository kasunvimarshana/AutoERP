<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Purchase\Domain\Entities\PurchaseOrderLine;

interface PurchaseOrderLineRepositoryInterface extends RepositoryInterface
{
    public function save(PurchaseOrderLine $line): PurchaseOrderLine;

    public function find(int|string $id, array $columns = ['*']): ?PurchaseOrderLine;

    public function findByPurchaseOrderId(int $purchaseOrderId): Collection;
}
