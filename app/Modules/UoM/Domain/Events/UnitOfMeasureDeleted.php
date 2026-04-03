<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class UnitOfMeasureDeleted extends BaseEvent
{
    public function __construct(public readonly int $unitId, int $tenantId)
    {
        parent::__construct($tenantId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->unitId,
        ]);
    }
}
