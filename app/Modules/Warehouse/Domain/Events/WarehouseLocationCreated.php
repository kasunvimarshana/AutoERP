<?php
namespace Modules\Warehouse\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class WarehouseLocationCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $locationId)
    {
        parent::__construct($tenantId);
    }
}
