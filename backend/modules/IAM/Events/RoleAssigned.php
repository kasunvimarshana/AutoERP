<?php

namespace Modules\IAM\Events;

use Modules\Core\Events\BaseEvent;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;

class RoleAssigned extends BaseEvent
{
    public function __construct(public User $user, public Role $role)
    {
        parent::__construct();
    }
}
