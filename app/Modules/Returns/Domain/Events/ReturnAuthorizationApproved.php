<?php

namespace Modules\Returns\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ReturnAuthorizationApproved extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $rmaId)
    {
        parent::__construct($tenantId);
    }
}
