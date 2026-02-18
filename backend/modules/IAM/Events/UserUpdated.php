<?php

namespace Modules\IAM\Events;

use Modules\Core\Events\BaseEvent;
use Modules\IAM\Models\User;

class UserUpdated extends BaseEvent
{
    public function __construct(public User $user, public array $changes = [])
    {
        parent::__construct();
    }
}
