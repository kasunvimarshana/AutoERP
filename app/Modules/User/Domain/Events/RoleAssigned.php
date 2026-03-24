<?php

declare(strict_types=1);

namespace Modules\User\Domain\Events;

use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\Entities\User;

class RoleAssigned
{
    public function __construct(public User $user, public Role $role) {}
}
