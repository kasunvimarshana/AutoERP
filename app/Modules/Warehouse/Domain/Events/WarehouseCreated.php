<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class WarehouseCreated extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly string $warehouseId,
    ) {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), ['warehouseId' => $this->warehouseId]);
    }
}
