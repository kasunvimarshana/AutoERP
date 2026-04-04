<?php
namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class InventoryBatchCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $batchId)
    {
        parent::__construct($tenantId);
    }
}
