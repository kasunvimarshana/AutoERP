<?php

namespace Modules\Returns\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class CreditMemoApplied extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $memoId)
    {
        parent::__construct($tenantId);
    }
}
