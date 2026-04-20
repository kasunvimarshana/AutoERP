<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Purchase\Domain\Entities\PurchaseOrder;

interface PurchaseOrderRepositoryInterface extends RepositoryInterface
{
    public function save(PurchaseOrder $order): PurchaseOrder;

    public function find(int|string $id, array $columns = ['*']): ?PurchaseOrder;
}
