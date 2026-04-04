<?php
namespace Modules\Configuration\Domain\Events;
use Modules\Core\Domain\Events\BaseEvent;

class OrganizationUnitCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $entityId)
    {
        parent::__construct($tenantId);
    }
}
