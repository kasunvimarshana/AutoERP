<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class SalesAllocationLineData
{
    public function __construct(
        public int $salesOrderLineId,
        public string $quantity,
    ) {}
}
