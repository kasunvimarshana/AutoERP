<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class CostAdjustmentLineData
{
    public function __construct(
        public int $valuationLayerId,
        public string $adjustmentAmount,
        public ?string $reason = null,
    ) {}
}
