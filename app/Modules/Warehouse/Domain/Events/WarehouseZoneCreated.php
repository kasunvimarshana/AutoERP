<?php
namespace Modules\Warehouse\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class WarehouseZoneCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $zoneId)
    {
        parent::__construct($tenantId);
    }
}
