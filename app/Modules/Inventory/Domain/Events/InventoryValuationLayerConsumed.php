<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;

class InventoryValuationLayerConsumed extends BaseEvent
{
    public function __construct(public readonly InventoryValuationLayer $layer)
    {
        parent::__construct($layer->getTenantId(), $layer->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->layer->getId(),
            'tenant_id' => $this->layer->getTenantId(),
        ]);
    }
}
