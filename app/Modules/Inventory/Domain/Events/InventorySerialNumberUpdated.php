<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Inventory\Domain\Entities\InventorySerialNumber;

class InventorySerialNumberUpdated extends BaseEvent
{
    public function __construct(public readonly InventorySerialNumber $serialNumber)
    {
        parent::__construct($serialNumber->getTenantId(), $serialNumber->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->serialNumber->getId(),
            'tenant_id' => $this->serialNumber->getTenantId(),
        ]);
    }
}
