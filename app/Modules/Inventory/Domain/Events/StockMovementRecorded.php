<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class StockMovementRecorded extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly string $movementId,
        public readonly string $productId,
        public readonly string $type,
        public readonly float $quantity,
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'movementId' => $this->movementId,
            'productId'  => $this->productId,
            'type'       => $this->type,
            'quantity'   => $this->quantity,
        ]);
    }
}
