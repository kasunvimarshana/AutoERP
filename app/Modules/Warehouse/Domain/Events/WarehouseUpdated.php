<?php
namespace Modules\Warehouse\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class WarehouseUpdated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $warehouseId)
    {
        parent::__construct($tenantId);
    }
}
