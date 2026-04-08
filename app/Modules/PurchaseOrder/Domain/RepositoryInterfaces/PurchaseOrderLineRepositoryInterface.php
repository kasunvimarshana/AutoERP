<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrderLine;

interface PurchaseOrderLineRepositoryInterface extends RepositoryInterface
{
    public function save(PurchaseOrderLine $line): PurchaseOrderLine;
    public function findByOrder(int $purchaseOrderId): Collection;
}
