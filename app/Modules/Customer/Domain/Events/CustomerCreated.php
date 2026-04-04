<?php
namespace Modules\Customer\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class CustomerCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $entityId)
    {
        parent::__construct($tenantId);
    }
}
