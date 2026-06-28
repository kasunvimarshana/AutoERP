<?php

declare(strict_types=1);

namespace Modules\Tax\Data;

final readonly class TaxReturnLineData
{
    public function __construct(
        public int $returnLineId,
        public string $sourceLineType,
        public ?int $sourceLineId,
        public string $returnedQuantity,
        public string $sourceQuantity,
    ) {}
}
