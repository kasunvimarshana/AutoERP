<?php

namespace Modules\Dispatch\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class DispatchData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $salesOrderId,
        public readonly int $warehouseId,
        public readonly string $dispatchNumber,
        public readonly array $lines = [],
        public readonly ?string $carrier = null,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $shippingAddress = null,
    ) {}
}
