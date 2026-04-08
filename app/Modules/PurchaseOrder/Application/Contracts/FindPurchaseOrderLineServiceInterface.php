<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindPurchaseOrderLineServiceInterface extends ReadServiceInterface
{
    public function findByOrder(int $purchaseOrderId): Collection;
}
