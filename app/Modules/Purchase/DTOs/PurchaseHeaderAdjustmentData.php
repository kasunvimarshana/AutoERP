<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

use Modules\Purchase\Enums\PurchaseAdjustmentAllocationMethod;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationBase;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;

final readonly class PurchaseHeaderAdjustmentData
{
    public function __construct(
        public string $name,
        public PurchaseAdjustmentType $adjustmentType,
        public PurchaseAdjustmentEffect $effect,
        public string $amount,
        public PurchaseAdjustmentCalculationType $calculationType = PurchaseAdjustmentCalculationType::Fixed,
        public PurchaseAdjustmentCalculationBase $calculationBase = PurchaseAdjustmentCalculationBase::Subtotal,
        public string $rate = '0.000000',
        public PurchaseAdjustmentAllocationMethod $allocationMethod = PurchaseAdjustmentAllocationMethod::Proportional,
        public bool $isAllocatable = true,
        public int $sortOrder = 0,
        public ?string $description = null,
        public ?string $costTreatment = null,
        public ?string $taxTreatment = null,
        public ?string $mappingSource = null,
        public ?string $overrideReason = null,
        /** @var list<array{client_line_key?: string|null, purchase_order_line_id?: int|null, amount: string}> */
        public array $manualAllocations = [],
    ) {}
}
