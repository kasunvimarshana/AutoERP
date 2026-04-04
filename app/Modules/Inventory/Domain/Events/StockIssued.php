<?php
namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class StockIssued extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $levelId)
    {
        parent::__construct($tenantId);
    }
}
