<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

final readonly class PurchaseReturnLineData
{
    public function __construct(
        public ?string $sourceLineType,
        public ?int $sourceLineId,
        public string $returnedQuantity,
        public ?int $itemId = null,
        public ?int $itemVariantId = null,
        public ?int $uomId = null,
        public ?string $unitPrice = null,
        public ?string $costBasis = null,
        public ?string $reason = null,
        public ?string $clientLineKey = null,
    ) {}
}
