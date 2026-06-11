<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

use Modules\Sales\Enums\SalesAdjustmentAllocationMethod;
use Modules\Sales\Enums\SalesAdjustmentCalculationBase;
use Modules\Sales\Enums\SalesAdjustmentCalculationType;
use Modules\Sales\Enums\SalesAdjustmentEffect;
use Modules\Sales\Enums\SalesAdjustmentType;

final readonly class SalesHeaderAdjustmentData
{
    public function __construct(
        public string $name,
        public SalesAdjustmentType $adjustmentType,
        public SalesAdjustmentEffect $effect,
        public string $amount,
        public SalesAdjustmentCalculationType $calculationType = SalesAdjustmentCalculationType::Fixed,
        public SalesAdjustmentCalculationBase $calculationBase = SalesAdjustmentCalculationBase::Subtotal,
        public string $rate = '0.000000',
        public SalesAdjustmentAllocationMethod $allocationMethod = SalesAdjustmentAllocationMethod::Proportional,
        public bool $isAllocatable = true,
        public int $sortOrder = 0,
        public ?string $description = null,
    ) {}
}
