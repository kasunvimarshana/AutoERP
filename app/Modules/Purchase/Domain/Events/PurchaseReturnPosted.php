<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Events;

class PurchaseReturnPosted
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $purchaseReturnId,
        public readonly int $supplierId,
    ) {}
}
