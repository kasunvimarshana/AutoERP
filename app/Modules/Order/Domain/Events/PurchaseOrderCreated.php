<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Events;

use Modules\Order\Domain\Entities\PurchaseOrder;

class PurchaseOrderCreated
{
    public function __construct(
        public readonly PurchaseOrder $purchaseOrder,
    ) {}
}
