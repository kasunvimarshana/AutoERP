<?php

namespace Modules\User\Domain\Events;

use Modules\User\Domain\Entities\User;

class UserCreated
{
    public function __construct(public User $user) {}
}
