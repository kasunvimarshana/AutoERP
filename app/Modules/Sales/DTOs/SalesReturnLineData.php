<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class SalesReturnLineData
{
    public function __construct(
        public string $returnedQuantity,
        public ?string $sourceLineType = null,
        public ?int $sourceLineId = null,
        public ?int $itemId = null,
        public ?int $itemVariantId = null,
        public ?int $uomId = null,
        public ?string $unitPrice = null,
        public ?string $costBasis = null,
        public string $conditionStatus = 'sellable',
        public ?string $reason = null,
    ) {}
}
