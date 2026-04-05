<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class WarehouseCreated extends BaseEvent
{
    public int $warehouseId;

    public function __construct(int $tenantId, int $warehouseId)
    {
        parent::__construct($tenantId);
        $this->warehouseId = $warehouseId;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), ['warehouseId' => $this->warehouseId]);
    }
}
