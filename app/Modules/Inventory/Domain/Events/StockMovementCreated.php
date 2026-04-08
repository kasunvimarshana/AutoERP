<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

final class StockMovementCreated extends BaseEvent
{
    public function __construct(
        public readonly mixed $movement,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'movement_id'   => $this->movement->id ?? null,
            'movement_type' => $this->movement->movement_type ?? null,
            'product_id'    => $this->movement->product_id ?? null,
            'quantity'      => $this->movement->quantity ?? null,
        ]);
    }
}
