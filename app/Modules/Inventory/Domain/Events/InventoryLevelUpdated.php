<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Inventory\Domain\Entities\InventoryLevel;

class InventoryLevelUpdated extends BaseEvent
{
    public function __construct(public readonly InventoryLevel $level)
    {
        parent::__construct($level->getTenantId(), $level->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->level->getId(),
            'tenant_id' => $this->level->getTenantId(),
        ]);
    }
}
