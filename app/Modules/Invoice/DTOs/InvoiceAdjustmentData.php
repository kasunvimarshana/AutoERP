<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;

final readonly class InvoiceAdjustmentData
{
    public function __construct(
        public string $name,
        public AdjustmentType $adjustmentType,
        public AdjustmentEffect $effect,
        public string $amount,
        public ?string $sourceAdjustmentType = null,
        public ?int $sourceAdjustmentId = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public string $calculationType = 'fixed',
        public string $rate = '0.000000',
        public ?string $sourceAmount = null,
        public AllocationMethod $allocationMethod = AllocationMethod::Manual,
        public bool $isSystemGenerated = false,
        public ?string $description = null,
    ) {}
}
