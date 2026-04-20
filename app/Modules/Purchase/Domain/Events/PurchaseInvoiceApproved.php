<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Events;

class PurchaseInvoiceApproved
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $purchaseInvoiceId,
        public readonly int $supplierId,
    ) {}
}
