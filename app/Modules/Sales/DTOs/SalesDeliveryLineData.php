<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class SalesDeliveryLineData
{
    public function __construct(
        public int $itemId,
        public string $deliveredQuantity,
        public string $unitPrice,
        public ?int $salesOrderLineId = null,
        public ?int $itemVariantId = null,
        public ?string $description = null,
        public ?int $uomId = null,
        public string $orderedQuantity = '0.000000',
    ) {}
}
