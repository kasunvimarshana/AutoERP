<?php

namespace Modules\Returns\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ReturnAuthorizationCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $rmaId)
    {
        parent::__construct($tenantId);
    }
}
