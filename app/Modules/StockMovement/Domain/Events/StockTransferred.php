<?php
namespace Modules\StockMovement\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;
class StockTransferred extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $fromMovementId, public readonly int $toMovementId)
    {
        parent::__construct($tenantId);
    }
}
