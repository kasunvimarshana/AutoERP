<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;

final readonly class PurchaseOrderLineData
{
    public function __construct(
        public int $itemId,
        public string $orderedQuantity,
        public string $unitPrice,
        public ?int $itemVariantId = null,
        public ?string $description = null,
        public ?int $uomId = null,
        public ?int $orderedUomId = null,
        public ?int $baseUomId = null,
        public string $uomConversionFactor = '1.000000',
        public ?string $baseQuantity = null,
        public PurchaseAdjustmentCalculationType $discountCalculationType = PurchaseAdjustmentCalculationType::Fixed,
        public string $discountRate = '0.000000',
        public string $discountAmount = '0.000000',
        public PurchaseAdjustmentCalculationType $taxCalculationType = PurchaseAdjustmentCalculationType::Fixed,
        public string $taxRate = '0.000000',
        public string $taxAmount = '0.000000',
        public PurchaseAdjustmentCalculationType $chargeCalculationType = PurchaseAdjustmentCalculationType::Fixed,
        public string $chargeRate = '0.000000',
        public string $chargeAmount = '0.000000',
        public ?int $taxGroupId = null,
    ) {}
}
