<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class WarehouseZoneDeleted extends BaseEvent
{
    public function __construct(public readonly int $zoneId, int $tenantId)
    {
        parent::__construct($tenantId, $zoneId);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->zoneId,
        ]);
    }
}
