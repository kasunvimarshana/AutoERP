<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

final readonly class PurchaseReturnLineData
{
    public function __construct(
        public string $sourceLineType,
        public int $sourceLineId,
        public string $returnedQuantity,
        public ?string $reason = null,
    ) {}
}
