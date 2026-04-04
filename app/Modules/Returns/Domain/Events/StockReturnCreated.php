<?php

namespace Modules\Returns\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class StockReturnCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $returnId)
    {
        parent::__construct($tenantId);
    }
}
