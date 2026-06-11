<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class StockAdjustmentLineData
{
    public function __construct(
        public int $itemId,
        public string $systemQuantity,
        public string $countedQuantity,
        public string $adjustmentQuantity,
        public string $unitCost = '0.000000',
        public ?int $itemVariantId = null,
        public ?int $batchId = null,
        public ?int $serialNumberId = null,
        public ?string $reason = null,
        public ?int $uomId = null,
    ) {}
}
