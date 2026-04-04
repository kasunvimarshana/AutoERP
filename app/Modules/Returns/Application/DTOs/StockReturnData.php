<?php

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class StockReturnData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly string $returnNumber,
        public readonly string $returnType,
        public readonly array $lines = [],
        public readonly ?int $customerId = null,
        public readonly ?int $supplierId = null,
        public readonly ?int $originalOrderId = null,
        public readonly ?string $originalOrderType = null,
        public readonly ?string $reason = null,
        public readonly ?string $notes = null,
    ) {}
}
