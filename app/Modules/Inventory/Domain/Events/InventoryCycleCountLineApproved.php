<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\Inventory\Domain\Entities\InventoryCycleCountLine;

class InventoryCycleCountLineApproved extends BaseEvent
{
    public function __construct(public readonly InventoryCycleCountLine $line)
    {
        parent::__construct($line->getTenantId(), $line->getId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->line->getId(),
            'tenant_id' => $this->line->getTenantId(),
        ]);
    }
}
