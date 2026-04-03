<?php

declare(strict_types=1);

namespace Modules\StockMovement\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\StockMovement\Domain\Entities\StockMovement;

class StockMovementCreated extends BaseEvent
{
    public function __construct(public readonly StockMovement $movement)
    {
        parent::__construct($movement->getTenantId(), $movement->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->movement->getId(),
            'tenant_id' => $this->movement->getTenantId(),
        ]);
    }
}
