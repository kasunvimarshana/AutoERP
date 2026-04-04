<?php
namespace Modules\Tenant\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class TenantDeleted extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $entityId)
    {
        parent::__construct($tenantId);
    }
}
