<?php

namespace Modules\IAM\Events;

use Modules\Core\Events\BaseEvent;
use Modules\IAM\Models\User;

class UserLoggedOut extends BaseEvent
{
    public function __construct(public User $user)
    {
        parent::__construct();
    }
}
