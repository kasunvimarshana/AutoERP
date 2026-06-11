<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

use Modules\Sales\Enums\SalesAdjustmentCalculationType;

final readonly class SalesLineData
{
    public function __construct(
        public int $itemId,
        public string $quantity,
        public string $unitPrice,
        public ?int $itemVariantId = null,
        public ?string $description = null,
        public ?int $uomId = null,
        public ?int $baseUomId = null,
        public ?string $baseQuantity = null,
        public ?int $sourceLineId = null,
        public SalesAdjustmentCalculationType $discountCalculationType = SalesAdjustmentCalculationType::Fixed,
        public string $discountRate = '0.000000',
        public string $discountAmount = '0.000000',
        public SalesAdjustmentCalculationType $taxCalculationType = SalesAdjustmentCalculationType::Fixed,
        public string $taxRate = '0.000000',
        public string $taxAmount = '0.000000',
        public SalesAdjustmentCalculationType $chargeCalculationType = SalesAdjustmentCalculationType::Fixed,
        public string $chargeRate = '0.000000',
        public string $chargeAmount = '0.000000',
    ) {}
}
