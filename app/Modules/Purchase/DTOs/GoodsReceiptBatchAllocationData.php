<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

final readonly class GoodsReceiptBatchAllocationData
{
    public function __construct(
        public string $quantity,
        public ?int $batchId = null,
        public ?string $batchNumber = null,
        public ?string $lotNumber = null,
        public ?string $manufactureDate = null,
        public ?string $expiryDate = null,
    ) {}
}
