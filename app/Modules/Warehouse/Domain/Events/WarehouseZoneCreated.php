<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Warehouse\Domain\Entities\WarehouseZone;

class WarehouseZoneCreated extends BaseEvent
{
    public function __construct(public readonly WarehouseZone $zone)
    {
        parent::__construct($zone->getTenantId(), $zone->getId());
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'          => $this->zone->getId(),
            'warehouseId' => $this->zone->getWarehouseId(),
            'name'        => $this->zone->getName()->value(),
            'code'        => $this->zone->getCode()?->value(),
            'type'        => $this->zone->getType(),
        ]);
    }
}
