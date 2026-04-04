<?php

namespace Modules\SalesOrder\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class SalesOrderData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly int $customerId,
        public readonly string $soNumber,
        public readonly array $lines = [],
        public readonly ?string $currency = 'USD',
        public readonly ?string $notes = null,
        public readonly ?string $shippingAddress = null,
        public readonly ?string $expectedDeliveryDate = null,
    ) {}
}
