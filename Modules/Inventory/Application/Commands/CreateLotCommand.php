<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Commands;

final readonly class CreateLotCommand
{
    public function __construct(
        public int $tenantId,
        public int $productId,
        public int $warehouseId,
        public ?string $lotNumber,
        public ?string $serialNumber,
        public ?string $batchNumber,
        public ?string $manufacturedDate,
        public ?string $expiryDate,
        public string $quantity,
        public ?string $notes,
    ) {}
}
