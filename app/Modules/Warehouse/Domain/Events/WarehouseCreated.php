<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Warehouse\Domain\Entities\Warehouse;

class WarehouseCreated extends BaseEvent
{
    public function __construct(public readonly Warehouse $warehouse)
    {
        parent::__construct($warehouse->getTenantId(), $warehouse->getId());
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'   => $this->warehouse->getId(),
            'name' => $this->warehouse->getName()->value(),
            'code' => $this->warehouse->getCode()?->value(),
            'type' => $this->warehouse->getType(),
        ]);
    }
}
