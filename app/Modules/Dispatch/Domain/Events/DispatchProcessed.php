<?php

namespace Modules\Dispatch\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class DispatchProcessed extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $dispatchId)
    {
        parent::__construct($tenantId);
    }
}
