<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Inventory\Domain\Entities\InventoryLocation;

class InventoryLocationDeleted extends BaseEvent
{
    public function __construct(public readonly InventoryLocation $location)
    {
        parent::__construct($location->getTenantId(), $location->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->location->getId(),
        ]);
    }
}
