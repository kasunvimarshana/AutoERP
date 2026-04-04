<?php

namespace Modules\Returns\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ReturnLineQualityCheckPassed extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $lineId)
    {
        parent::__construct($tenantId);
    }
}
