<?php

namespace Modules\SalesOrder\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class SalesOrderPickingStarted extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $soId)
    {
        parent::__construct($tenantId);
    }
}
