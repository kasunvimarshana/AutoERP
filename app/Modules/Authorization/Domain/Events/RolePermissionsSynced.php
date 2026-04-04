<?php
namespace Modules\Authorization\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class RolePermissionsSynced extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly int $roleId,
        public readonly array $permissionIds,
    ) {
        parent::__construct($tenantId);
    }
}
