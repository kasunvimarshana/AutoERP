<?php
namespace Modules\StockMovement\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;
class StockMovementCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $movementId)
    {
        parent::__construct($tenantId);
    }
}
