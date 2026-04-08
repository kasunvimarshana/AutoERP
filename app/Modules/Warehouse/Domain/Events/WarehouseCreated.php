<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

final class WarehouseCreated extends BaseEvent
{
    public function __construct(
        public readonly mixed $warehouse,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'warehouse_id' => is_object($this->warehouse) ? $this->warehouse->id : $this->warehouse,
        ]);
    }
}
