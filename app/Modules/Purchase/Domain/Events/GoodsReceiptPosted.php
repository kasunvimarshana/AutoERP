<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Events;

class GoodsReceiptPosted
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $grnHeaderId,
        public readonly int $supplierId,
        public readonly int $warehouseId,
        public readonly array $lines,
    ) {}
}
