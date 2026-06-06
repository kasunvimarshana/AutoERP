<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

final readonly class PurchaseOrderLineData
{
    public function __construct(
        public int $itemId,
        public string $orderedQuantity,
        public string $unitPrice,
        public ?int $itemVariantId = null,
        public ?string $description = null,
        public ?int $uomId = null,
        public string $discountAmount = '0.000000',
        public string $taxAmount = '0.000000',
        public string $chargeAmount = '0.000000',
    ) {}
}
