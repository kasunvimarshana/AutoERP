<?php

namespace Modules\User\Domain\Events;

use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Entities\Role;

class RoleAssigned
{
    public function __construct(public User $user, public Role $role) {}
}
