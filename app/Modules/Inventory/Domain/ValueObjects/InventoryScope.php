<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

final readonly class InventoryScope
{
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public int $itemId,
        public ?int $variantId,
        public ?int $warehouseId,
        public ?int $locationId,
        public ?int $batchId,
        public ?int $serialId,
        public int $uomId,
    ) {
    }
}
