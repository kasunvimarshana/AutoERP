<?php
namespace Modules\Authorization\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class PermissionDeleted extends BaseEvent
{
    public function __construct(int $tenantId, public readonly int $permissionId)
    {
        parent::__construct($tenantId);
    }
}
