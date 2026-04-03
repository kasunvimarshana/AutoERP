<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Inventory\Domain\Entities\InventoryBatch;

class InventoryBatchDeleted extends BaseEvent
{
    public function __construct(public readonly InventoryBatch $batch)
    {
        parent::__construct($batch->getTenantId(), $batch->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id' => $this->batch->getId(),
        ]);
    }
}
