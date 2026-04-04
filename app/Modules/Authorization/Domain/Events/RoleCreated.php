<?php
namespace Modules\Authorization\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class RoleCreated extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $roleId)
    {
        parent::__construct($tenantId);
    }
}
