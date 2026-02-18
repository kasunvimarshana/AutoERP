<?php

namespace Modules\IAM\Events;

use Modules\Core\Events\BaseEvent;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

class PermissionGranted extends BaseEvent
{
    public function __construct(public Role $role, public Permission $permission)
    {
        parent::__construct();
    }
}
