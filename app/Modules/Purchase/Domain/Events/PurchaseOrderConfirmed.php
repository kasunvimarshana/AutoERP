<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Events;

class PurchaseOrderConfirmed
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $purchaseOrderId,
        public readonly int $supplierId,
    ) {}
}
