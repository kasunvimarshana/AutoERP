<?php
namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class InventoryReconciled extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $countId)
    {
        parent::__construct($tenantId);
    }
}
