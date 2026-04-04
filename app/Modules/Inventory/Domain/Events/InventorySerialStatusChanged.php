<?php
namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class InventorySerialStatusChanged extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $serialId)
    {
        parent::__construct($tenantId);
    }
}
