<?php
namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ValuationLayerCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $layerId)
    {
        parent::__construct($tenantId);
    }
}
