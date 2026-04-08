<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;

class InventoryCycleCountCancelled extends BaseEvent
{
    public function __construct(public readonly InventoryCycleCount $cycleCount)
    {
        parent::__construct($cycleCount->getTenantId(), $cycleCount->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->cycleCount->getId(),
            'tenant_id' => $this->cycleCount->getTenantId(),
        ]);
    }
}
