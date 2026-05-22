<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryMovementValuated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $movementId,
        public readonly int $tenantId,
        public readonly ?int $organizationUnitId,
        public readonly int $itemId,
        public readonly ?int $variantId,
        public readonly string $direction,
        public readonly string $txnType,
        public readonly float $quantity,
        public readonly float $unitCost,
        public readonly float $totalCost,
        public readonly array $metadata = [],
    ) {
    }
}
