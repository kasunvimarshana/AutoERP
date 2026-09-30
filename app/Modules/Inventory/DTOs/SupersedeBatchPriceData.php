<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class SupersedeBatchPriceData
{
    public function __construct(
        public BatchPriceData $price,
        public int $expectedVersion,
        public string $correctionReason,
    ) {}
}
